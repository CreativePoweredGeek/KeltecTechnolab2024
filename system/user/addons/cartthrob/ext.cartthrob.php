<?php

use CartThrob\Dependency\GuzzleHttp\Client as Guzzle;
use CartThrob\Dependency\GuzzleHttp\Exception\GuzzleException;
use CartThrob\Dependency\GuzzleHttp\Psr7\Uri;
use CartThrob\Plugins\Notification\NotificationPlugin;
use ExpressionEngine\Model\Member\Member as MemberModel;
use ExpressionEngine\Service\CustomMenu\Menu;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @property CI_Controller $EE
 * @property Cartthrob_cart $cart
 * @property Cartthrob_store $store
 */
class Cartthrob_ext
{
    public const ASYNC_METHOD_HTTP = 1;
    public const ASYNC_METHOD_CRON = 2;

    public $settings = [];
    public $name = 'CartThrob';
    public $version;
    public $description = 'CartThrob Shopping Cart';
    public $settings_exist = 'y';
    public $docs_url = 'https://www.cartthrob.com/docs/';
    public $required_by = ['module'];

    /**
     * Cartthrob_ext constructor.
     * @param string $settings
     */
    public function __construct($settings = '')
    {
        $this->version = CARTTHROB_VERSION;
    }

    public function settings_form()
    {
        ee()->functions->redirect(ee('CP/URL')->make('addons/settings/cartthrob'));
    }

    /**
     * @return bool
     */
    public function activate_extension(): bool
    {
        return true;
    }

    /**
     * @param string
     * @return bool False if the extension is current
     */
    public function update_extension($current = ''): bool
    {
        if ($current == '' or $current == $this->version) {
            return false;
        }

        ee()->db->update('extensions', ['version' => $this->version], ['class' => __CLASS__]);

        return true;
    }

    /**
     * @param null
     */
    public function disable_extension()
    {
        ee()->db->delete('extensions', ['class' => __CLASS__]);
    }

    public function settings()
    {
        // Pass
    }

    /**
     * @param MemberModel $member
     * @param $value
     * @return void
     */
    public function before_member_delete(MemberModel $member, $value)
    {
        $super_admin = ee('cartthrob:SettingsService')->get('cartthrob', 'default_member_id');
        if ($member->isSuperAdmin()) {
            if ($super_admin == $member->member_id) {
                show_error(lang('cannot_remove_sa_account'));
                exit;
            }
        }
    }

    /**
     * @param array $delete_ids
     * @return void
     */
    public function before_member_bulk_delete(array $delete_ids)
    {
        $super_admin = ee('cartthrob:SettingsService')->get('cartthrob', 'default_member_id');
        if (in_array($super_admin, $delete_ids)) {
            show_error(lang('cannot_remove_sa_account'));
            exit;
        }
    }

    /**
     * @param $channel_form
     * @return array
     */
    public function channel_form_submit_entry_start($channel_form)
    {
        if (!$this->isCartthrobChannel(ee()->input->post('channel_id', true))) {
            return $channel_form;
        }

        $data = [
            'entry_id' => ee()->input->post('entry_id'),
            'channel_id' => ee()->input->post('channel_id', true),
            'status' => ee()->input->post('status', true),
        ];

        if (ee()->input->post('entry_id')) {
            ee()->load->model('channel_entries_model');
            $entry = ee()->channel_entries_model->get_entry($data['entry_id'], $data['channel_id']);

            if ($entry->num_rows() > 0) {
                $row = $entry->row();
                $data['revision_post']['status'] = $row->status;
            }
        }

        return $this->publish_form_entry_data($data);
    }

    /**
     * Test if the entries channel is a CartThrob channel
     *
     * @param $channelId
     * @return bool
     */
    private function isCartthrobChannel($channelId): bool
    {
        ee()->load->model('cartthrob_settings_model');

        $settings = ee()->cartthrob_settings_model->get_settings();
        $cartthrobChannels = [$settings['orders_channel']];

        return in_array($channelId, $cartthrobChannels);
    }

    /**
     * @param array
     * @return array
     */
    public function publish_form_entry_data($data = []): array
    {
        if (isset($data['channel_id']) && !$this->isCartthrobChannel($data['channel_id'])) {
            return $data;
        }

        ee()->load->model('cartthrob_settings_model');

        if (ee()->config->item('cartthrob:save_orders') && ee()->config->item('cartthrob:orders_channel')) {
            if (!empty($data['entry_id']) && !empty($data['status'])) {
                if ($data['channel_id'] == ee()->config->item('cartthrob:orders_channel')) {
                    $this->setStatus($data['entry_id'], $data['status']);
                }

                if (!empty($data['entry_id']) && !empty($data['revision_post']['status'])) {
                    $this->setStatus($data['entry_id'], $data['revision_post']['status']);
                }
            }
        }

        return $data;
    }

    /**
     * @param $entry_id
     * @param $status
     */
    private function setStatus($entry_id, $status)
    {
        ee()->session->cache['cartthrob'][$entry_id]['status'] = $status;
    }

    /**
     * @param $channel_form
     */
    public function channel_form_submit_entry_end($channel_form)
    {
        if (!$this->isCartthrobChannel(ee()->input->post('channel_id', true))) {
            return;
        }

        $data = [
            'entry_id' => ee()->input->post('entry_id'),
            'channel_id' => ee()->input->post('channel_id', true),
            'status' => ee()->input->post('status', true),
            'revision_post' => [
                'status' => 'open',
            ],
        ];

        if (ee()->input->post('entry_id')) {
            $data['revision_post']['status'] = $channel_form->entry('status');
        }
    }

    /**
     * @param $entry
     * @param $values
     * @param $modified
     */
    public function before_channel_entry_update($entry, $values, $modified)
    {
        if (!$this->isCartthrobChannel($entry->channel_id)) {
            return;
        }

        $old_status = ee('Model')
            ->get('ChannelEntry')
            ->fields('status')
            ->filter('entry_id', $entry->entry_id)
            ->first();

        if (!is_null($old_status)) {
            $this->setStatus($entry->entry_id, $old_status->status);
        }
    }

    /**
     * @param $entry
     * @param $values
     * @param $modified
     */
    public function after_channel_entry_update($entry, $values, $modified)
    {
        if (!$this->isCartthrobChannel($entry->channel_id)) {
            return;
        }

        $status_start = $this->getLastStatus($entry->entry_id, $entry->status);

        $this->sendStatusChangeEmails($status_start, $values);
    }

    /**
     * Get the entry's last status value
     *
     * @param $entryId
     * @param $status
     * @return bool|string
     */
    private function getLastStatus($entryId, $status)
    {
        $lastStatus = ee()->session->cache['cartthrob'][$entryId]['status'] ?? '';

        if ($status == null || empty($lastStatus)) {
            return false;
        }

        return $lastStatus;
    }

    /**
     * Send CT emails when an entry status changes
     *
     * @param string $status_start the original status
     * @param array $data array of entry data
     * @return void
     */
    private function sendStatusChangeEmails(string $status_start, array $data): void
    {
        $data['previous_status'] = $status_start;

        $notifications = ee('cartthrob:NotificationsService')->getNotificationsForEvent('status_change', $status_start, $data['status']);
        if ($notifications) {
            foreach ($notifications as $notification) {
                if ($notification instanceof NotificationPlugin) {
                    $notification->send($data);
                }
            }
        }
    }

    /**
     * @param int $entry_id
     * @param array $meta
     * @param array $data
     * @return array
     */
    public function entry_submission_end(int $entry_id, array $meta = [], array $data = [])
    {
        if (empty($data['revision_post']['status'])) {
            return $data;
        }

        $status_start = $this->getLastStatus($entry_id, $data['revision_post']['status']);

        $this->sendStatusChangeEmails($status_start, $data['revision_post']);
    }

    /**
     * @param array $meta
     * @param array $data
     * @param bool $autosave
     * @return array
     */
    public function submission_ready(array $meta = [], array $data = [], bool $autosave = false): array
    {
        ee()->load->model('cartthrob_settings_model');

        if (empty($data['revision_post']['status'])) {
            return $data;
        }

        $status_start = $this->getLastStatus($data['entry_id'], $data['revision_post']['status']);
        $data['revision_post']['previous_status'] = $status_start;

        $this->sendStatusChangeEmails($status_start, $data['revision_post']);

        return $data;
    }

    /**
     * Perform additional actions after logout
     * @return void
     */
    public function member_member_logout(): void
    {
        if (ee()->cartthrob->store->config('clear_session_on_logout')) {
            ee()->cartthrob_session->destroy();
        } elseif (ee()->cartthrob->store->config('clear_cart_on_logout') && !ee()->cartthrob->cart->is_empty()) {
            ee()->cartthrob->cart
                ->clearAll()
                ->save();
        }
    }

    /**
     * @param $userdata
     */
    public function member_member_login($userdata)
    {
        // attach the user's member id to this cart
        if (!empty($userdata->member_id) && isset(ee()->cartthrob_session) && ee()->cartthrob_session->session_id()) {
            ee()->cartthrob_session->update(['member_id' => $userdata->member_id]);
            ee()->cartthrob_session->setup_sticky_cart();
        }
    }

    /**
     * @param $menu
     * @return mixed
     */
    public function cp_menu_array($menu)
    {
        if (ee()->extensions->last_call !== false) {
            $menu = ee()->extensions->last_call;
        }

        return $menu;
    }

    /**
     * @param $menu
     */
    public function cp_custom_menu(Menu $menu)
    {
        ee()->lang->loadfile('cartthrob_routes', 'cartthrob');
        $sidebar_data = ee('cartthrob:SidebarService')->toArray();
        $heading = ee('cartthrob:SettingsService')->get('cartthrob', 'cp_menu_label');
        if (!$heading) {
            $heading = lang('ct.custom_sidebar.default_title');
        }
        $subMenu = $menu->addSubmenu($heading);
        foreach ($sidebar_data as $title => $sidebar) {
            if (isset($sidebar['list']) && is_array($sidebar['list'])) {
                foreach ($sidebar['list'] as $_title => $path) {
                    $url = $path['path'] ?? $path;
                    $with_base_url = $path['with_base_url'] ?? true;
                    if ($with_base_url) {
                        $subMenu->addItem(lang($_title), ee('CP/URL')->make('addons/settings/cartthrob/' . $url));
                    } else {
                        $subMenu->addItem(lang($_title), ee('CP/URL')->make($url));
                    }
                }
            }
        }

        return $menu;
    }

    /**
     * Instantiate CartThrob on Core Boot
     */
    public function core_boot()
    {
        ee()->load->library(['cartthrob_loader', 'paths']);

        if (ee()->extensions->active_hook('cartthrob_boot') === true) {
            ee()->extensions->call('cartthrob_boot');
        }

        $actionId = (int)ee()->functions->insert_action_ids(ee()->functions->fetch_action_id('Cartthrob', 'consume_async_job_action'));

        if ((int)ee()->input->get('ACT', null) === $actionId || self::ASYNC_METHOD_HTTP !== (int)ee()->cartthrob->config('orders_async_method')) {
            return;
        }

        $client = new Guzzle();
        $url = new Uri(ee()->paths->build_action_url('Cartthrob', 'consume_async_job_action'));

        if ($workerUrl = ee()->cartthrob->config('orders_async_worker_base_url')) {
            $query = [];
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
            $url = Uri::withQueryValues(new Uri($workerUrl), $query);
        }

        try {
            $client->request('GET', $url, [
                'headers' => ['Referer' => $_SERVER['HTTP_REFERER'] ?? ''],
            ]);
        } catch (GuzzleException $e) {
            // pass
        }
    }
}
