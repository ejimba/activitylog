<?php

namespace Rmsramos\Activitylog\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Rmsramos\Activitylog\Helpers\ActivityLogHelper;

class ActivityNotificationService
{
    /**
     * Register activity notification listeners.
     */
    public function register(): void
    {
        // Register event listeners for activity notifications
        // This can be expanded to include actual notification logic
    }

    /**
     * Get the user model class from configuration.
     */
    protected function getUserModel(): string
    {
        return ActivityLogHelper::getUserModelClass() ?? 'App\\Models\\User';
    }

    /**
     * Determine if an activity should trigger a notification.
     */
    public function shouldNotify(Model $activity): bool
    {
        if (! $this->isActivityInstance($activity)) {
            return false;
        }

        $config = config('filament-activitylog.notifications');

        // Check if notifications are enabled
        if (! ($config['enabled'] ?? false)) {
            return false;
        }

        // Check if this event should trigger notification
        if (! $this->isNotifiableEvent($activity->event)) {
            return false;
        }

        // Check quiet hours
        if ($this->isQuietHours()) {
            return false;
        }

        return true;
    }

    /**
     * Check if an event should trigger notifications.
     */
    protected function isNotifiableEvent(string $event): bool
    {
        $config = config('filament-activitylog.notifications.events', []);

        return $config[$event] ?? false;
    }

    /**
     * Get users who should be notified about an activity.
     */
    public function getNotifiableUsers(Model $activity): Collection
    {
        if (! $this->isActivityInstance($activity)) {
            return collect();
        }

        $users = collect();

        // Get users by role
        $users = $users->merge($this->getUsersByRole($activity));

        // Add specific users from config
        $users = $users->merge($this->getSpecificUsers($activity));

        // Add record owner if applicable
        if ($owner = $this->getRecordOwner($activity)) {
            $users->push($owner);
        }

        // Remove duplicates and filter by user preferences
        return $users->unique('id')->filter(function ($user) use ($activity) {
            return $this->userWantsNotification($user, $activity);
        });
    }

    /**
     * Get users by role configuration.
     */
    protected function getUsersByRole(Model $activity): Collection
    {
        if (! $this->isActivityInstance($activity)) {
            return collect();
        }

        $config    = config('filament-activitylog.notifications.roles', []);
        $users     = collect();
        $userModel = $this->getUserModel();

        foreach ($config as $role => $events) {
            if (! (in_array($activity->event, $events, true) || in_array('*', $events, true))) {
                continue;
            }

            if ($this->supportsRoleScope($userModel)) {
                $roleUsers = $userModel::role($role)->get();
                $users     = $users->merge($roleUsers);

                continue;
            }

            $users = $users->merge($this->resolveUsersByRoleRelationship($userModel, $role));
        }

        return $users;
    }

    protected function supportsRoleScope(string $userModel): bool
    {
        return ActivityLogHelper::classUsesTrait($userModel, \Spatie\Permission\Traits\HasRoles::class);
    }

    protected function resolveUsersByRoleRelationship(string $userModel, string $role): Collection
    {
        if (! method_exists($userModel, 'roles')) {
            return collect();
        }

        return $userModel::whereHas('roles', function ($query) use ($role): void {
            $query->where('name', $role);
        })->get();
    }

    /**
     * Get specific users from config.
     */
    protected function getSpecificUsers(Model $activity): Collection
    {
        if (! $this->isActivityInstance($activity)) {
            return collect();
        }

        $userIds = config('filament-activitylog.notifications.notify_users', []);

        if (empty($userIds)) {
            return collect();
        }

        $userModel = $this->getUserModel();

        return $userModel::whereIn('id', $userIds)->get();
    }

    /**
     * Get the record owner if applicable.
     */
    protected function getRecordOwner(Model $activity): ?Model
    {
        if (! $this->isActivityInstance($activity) || ! $activity->subject) {
            return null;
        }

        $userModel = $this->getUserModel();

        // Check if subject has a user relationship
        if (method_exists($activity->subject, 'user')) {
            return $activity->subject->user;
        }

        // Check if subject is a User
        if ($activity->subject instanceof $userModel) {
            return $activity->subject;
        }

        return null;
    }

    /**
     * Check if user wants to receive notification for this activity.
     */
    protected function userWantsNotification(Model $user, Model $activity): bool
    {
        // Don't notify the user who performed the action
        if (! $this->isActivityInstance($activity)) {
            return false;
        }

        if ($activity->causer_id === $user->id) {
            return false;
        }

        // This method can be overridden in your application
        // to implement custom notification preferences logic
        return true;
    }

    /**
     * Check if current time is within quiet hours.
     */
    protected function isQuietHours(): bool
    {
        $config = config('filament-activitylog.notifications.quiet_hours');

        if (! ($config['enabled'] ?? false)) {
            return false;
        }

        $now   = now();
        $start = Carbon::parse($config['start']);
        $end   = Carbon::parse($config['end']);

        // Handle overnight quiet hours (e.g., 22:00 to 08:00)
        if ($start->greaterThan($end)) {
            return $now->greaterThanOrEqualTo($start) || $now->lessThanOrEqualTo($end);
        }

        return $now->between($start, $end);
    }

    /**
     * Notify users about an activity.
     * Override this method in your application to implement custom notification logic.
     */
    public function notify(Activity $activity): void
    {
        // This method should be implemented in your application
        // based on your specific notification requirements
    }

    /**
     * Check if activity is critical.
     */
    protected function isCriticalActivity(Activity $activity): bool
    {
        // Check if event is marked as critical
        $criticalEvents = config('filament-activitylog.notifications.critical_events', [
            'deleted',
            'payment.failed',
        ]);

        if (in_array($activity->event, $criticalEvents)) {
            return true;
        }

        // Check threshold values
        return $this->exceedsThreshold($activity);
    }

    /**
     * Check if activity exceeds configured thresholds.
     */
    protected function exceedsThreshold(Activity $activity): bool
    {
        $thresholds = config('filament-activitylog.notifications.thresholds', []);
        $properties = $activity->properties ?? [];

        foreach ($thresholds as $key => $threshold) {
            if (isset($properties[$key]) && $properties[$key] > $threshold) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the reason why activity is critical.
     */
    protected function getCriticalReason(Activity $activity): string
    {
        $criticalEvents = config('filament-activitylog.notifications.critical_events', []);

        if (in_array($activity->event, $criticalEvents)) {
            return "Event '{$activity->event}' is marked as critical";
        }

        if ($this->exceedsThreshold($activity)) {
            $thresholds = config('filament-activitylog.notifications.thresholds', []);
            $properties = $activity->properties ?? [];

            foreach ($thresholds as $key => $threshold) {
                if (isset($properties[$key]) && $properties[$key] > $threshold) {
                    return "Value for '{$key}' ({$properties[$key]}) exceeds threshold ({$threshold})";
                }
            }
        }

        return 'Unknown critical condition';
    }

    /**
     * Send digest notifications.
     */
    public function sendDigest(string $period = 'daily'): void
    {
        $config = config('filament-activitylog.notifications.digest');

        if (! ($config['enabled'] ?? false)) {
            return;
        }

        $minActivities = $config['min_activities'] ?? 5;

        // Get activities for the period
        $activities = $this->getActivitiesForPeriod($period);

        if ($activities->count() < $minActivities) {
            return;
        }

        // This method should be implemented in your application
        // based on your specific digest notification requirements
    }

    /**
     * Get activities for a specific period.
     */
    protected function getActivitiesForPeriod(string $period): Collection
    {
        $query = ActivityLogHelper::activityQuery();

        switch ($period) {
            case 'daily':
                $query->where('created_at', '>=', now()->subDay());

                break;
            case 'weekly':
                $query->where('created_at', '>=', now()->subWeek());

                break;
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Ensure the provided model is the configured activity model.
     */
    protected function isActivityInstance(Model $activity): bool
    {
        $activityClass = ActivityLogHelper::getActivityModelClass();

        return $activity instanceof $activityClass;
    }
}
