<?php

namespace Biigle\Modules\UserDisks\Notifications;

use Biigle\Modules\UserDisks\UserDisk;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class UserDiskExpiresSoon extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The storage disk that was confirmed
     *
     * @var UserDisk
     */
    protected $disk;

    /**
     * Ignore this job if the disk does not exist any more.
     *
     * @var bool
     */
    protected $deleteWhenMissingModels = true;

    /**
     * Create a new notification instance.
     *
     * @param UserDisk $disk
     * @param string $reason
     * @return void
     */
    public function __construct(UserDisk $disk)
    {
        $this->disk = $disk;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $settings = config('user_disks.notifications.default_settings');

        if (config('user_disks.notifications.allow_user_settings') === true) {
            $settings = $notifiable->getSettings('user_disk_notifications', $settings);
        }

        if ($settings === 'web') {
            return ['database'];
        }

        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $diff = $this->disk->expires_at->diffForHumans();

        $message = (new MailMessage)
            ->subject('Your BIIGLE storage disk will expire soon')
            ->line("Your storage disk will expire {$diff}.")
            ->action("View storage disk", route('storage-disks'));

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $diff = $this->disk->expires_at->diffForHumans();

        $array = [
            'title' => 'Your storage disk will expire soon',
            'message' => "Your storage disk will expire {$diff}.",
            'action' => 'View storage disk',
            'actionLink' => route('storage-disks'),
        ];

        return $array;
    }
}
