<h4>Storage disk notifications</h4>
<p class="text-muted">
    Notifications about the expiration of storage disks.
</p>
<form id="user-disk-notification-settings">
    <div class="form-group">
        <label class="radio-inline">
            <input type="radio" v-model="settings" value="email"> <strong>Email</strong>
        </label>
        <label class="radio-inline">
            <input type="radio" v-model="settings" value="web"> <strong>Web</strong>
        </label>
        <span v-cloak>
            <loader v-if="loading" :active="true"></loader>
            <span v-else>
                <i v-if="saved" class="fa fa-check text-success"></i>
                <i v-if="error" class="fa fa-times text-danger"></i>
            </span>
        </span>
    </div>
</form>

@push('scripts')
<script type="text/javascript">
    biigle.$mount('user-disk-notification-settings', {
        mixins: [biigle.$require('core.mixins.notificationSettings')],
        data: {
            settings: '{!! $user->getSettings('storage_disk_notifications', config('user_disks.notifications.default_settings')) !!}',
            settingsKey: 'storage_disk_notifications',
        },
    });
</script>
@endpush
