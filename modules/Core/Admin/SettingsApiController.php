<?php

namespace Modules\Core\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Models\Settings;

class SettingsApiController extends Controller
{
    public function payment(): JsonResponse
    {
        if ($permission = $this->checkPermission()) {
            return $permission;
        }

        $gateways = get_payment_gateways();
        $data = [];

        foreach ($gateways as $gatewayId => $gatewayObj) {
            $optionDefs = $gatewayObj->getOptionsConfigs();
            $options = [];

            foreach ($optionDefs as $def) {
                $id = $def['id'] ?? null;
                if (!$id) {
                    continue;
                }

                $default = $def['std'] ?? '';
                $key = 'g_' . $gatewayId . '_' . $id;
                $value = setting_item($key, $default);

                $options[$id] = [
                    'key' => $key,
                    'type' => $def['type'] ?? 'input',
                    'label' => $def['label'] ?? $id,
                    'value' => $value,
                    'default' => $default,
                ];
            }

            $data[$gatewayId] = [
                'id' => $gatewayId,
                'name' => $gatewayObj->name ?? $gatewayId,
                'enabled' => (bool) setting_item('g_' . $gatewayId . '_enable', 0),
                'options' => $options,
            ];
        }

        return response()->json(['data' => $data]);
    }

    public function updatePayment(Request $request): JsonResponse
    {
        if ($permission = $this->checkPermission()) {
            return $permission;
        }

        $gateways = get_payment_gateways();
        $saved = [];

        // Supports two request styles:
        // 1) Flat keys: { "g_chapa_enable": 1, "g_chapa_secret_key": "..." }
        // 2) Nested: { "gateways": { "chapa": { "enable": 1, "secret_key": "..." } } }
        $flat = $request->all();
        foreach ($flat as $key => $val) {
            if (!is_string($key) || strpos($key, 'g_') !== 0) {
                continue;
            }
            setting_update_item($key, is_array($val) ? json_encode($val) : $val);
            $saved[$key] = $val;
        }

        $nestedGateways = $request->input('gateways', []);
        if (is_array($nestedGateways)) {
            foreach ($nestedGateways as $gatewayId => $options) {
                if (!isset($gateways[$gatewayId]) || !is_array($options)) {
                    continue;
                }
                foreach ($options as $optionId => $val) {
                    $key = 'g_' . $gatewayId . '_' . $optionId;
                    setting_update_item($key, is_array($val) ? json_encode($val) : $val);
                    $saved[$key] = $val;
                }
            }
        }

        return response()->json([
            'message' => 'Payment settings saved',
            'updated' => array_keys($saved),
        ]);
    }

    public function hotel(): JsonResponse
    {
        return $this->respondSettingsGroup('hotel');
    }

    public function updateHotel(Request $request): JsonResponse
    {
        return $this->updateSettingsGroup($request, 'hotel');
    }

    public function space(): JsonResponse
    {
        return $this->respondSettingsGroup('space');
    }

    public function updateSpace(Request $request): JsonResponse
    {
        return $this->updateSettingsGroup($request, 'space');
    }

    public function car(): JsonResponse
    {
        return $this->respondSettingsGroup('car');
    }

    public function updateCar(Request $request): JsonResponse
    {
        return $this->updateSettingsGroup($request, 'car');
    }

    public function event(): JsonResponse
    {
        return $this->respondSettingsGroup('event');
    }

    public function updateEvent(Request $request): JsonResponse
    {
        return $this->updateSettingsGroup($request, 'event');
    }

    public function tour(): JsonResponse
    {
        return $this->respondSettingsGroup('tour');
    }

    public function updateTour(Request $request): JsonResponse
    {
        return $this->updateSettingsGroup($request, 'tour');
    }

    public function boat(): JsonResponse
    {
        return $this->respondSettingsGroup('boat');
    }

    public function updateBoat(Request $request): JsonResponse
    {
        return $this->updateSettingsGroup($request, 'boat');
    }

    public function news(): JsonResponse
    {
        return $this->respondSettingsGroup('news');
    }

    public function updateNews(Request $request): JsonResponse
    {
        return $this->updateSettingsGroup($request, 'news');
    }

    public function booking(): JsonResponse
    {
        return $this->respondSettingsGroup('booking');
    }

    public function updateBooking(Request $request): JsonResponse
    {
        return $this->updateSettingsGroup($request, 'booking');
    }

    public function enquiry(): JsonResponse
    {
        return $this->respondSettingsGroup('enquiry');
    }

    public function updateEnquiry(Request $request): JsonResponse
    {
        return $this->updateSettingsGroup($request, 'enquiry');
    }

    public function user(): JsonResponse
    {
        return $this->respondSettingsGroup('user');
    }

    public function updateUser(Request $request): JsonResponse
    {
        return $this->updateSettingsGroup($request, 'user');
    }

    public function vendor(): JsonResponse
    {
        return $this->respondSettingsGroup('vendor');
    }

    public function updateVendor(Request $request): JsonResponse
    {
        return $this->updateSettingsGroup($request, 'vendor');
    }

    public function style(): JsonResponse
    {
        return $this->respondSettingsGroup('style');
    }

    public function updateStyle(Request $request): JsonResponse
    {
        return $this->updateSettingsGroup($request, 'style');
    }

    public function soloTour(): JsonResponse
    {
        return $this->respondSettingsGroup('solo_tour');
    }

    public function updateSoloTour(Request $request): JsonResponse
    {
        return $this->updateSettingsGroup($request, 'solo_tour');
    }

    public function advance(): JsonResponse
    {
        return $this->respondSettingsGroup('advance');
    }

    public function updateAdvance(Request $request): JsonResponse
    {
        return $this->updateSettingsGroup($request, 'advance');
    }

    public function media(): JsonResponse
    {
        return $this->respondSettingsGroup('media');
    }

    public function updateMedia(Request $request): JsonResponse
    {
        return $this->updateSettingsGroup($request, 'media');
    }

    public function email(): JsonResponse
    {
        return $this->respondSettingsGroup('email');
    }

    public function updateEmail(Request $request): JsonResponse
    {
        return $this->updateSettingsGroup($request, 'email');
    }

    public function sms(): JsonResponse
    {
        return $this->respondSettingsGroup('sms');
    }

    public function updateSms(Request $request): JsonResponse
    {
        return $this->updateSettingsGroup($request, 'sms');
    }

    public function support(): JsonResponse
    {
        return $this->respondSettingsGroup('support');
    }

    public function updateSupport(Request $request): JsonResponse
    {
        return $this->updateSettingsGroup($request, 'support');
    }

    public function review(): JsonResponse
    {
        return $this->respondSettingsGroup('review');
    }

    public function updateReview(Request $request): JsonResponse
    {
        return $this->updateSettingsGroup($request, 'review');
    }

    protected function respondSettingsGroup(string $groupId): JsonResponse
    {
        if ($permission = $this->checkPermission()) {
            return $permission;
        }

        $group = $this->getSettingsGroup($groupId);
        if (!$group) {
            return response()->json(['message' => ucfirst($groupId) . ' settings group not found'], 404);
        }

        $keys = (array) ($group['keys'] ?? []);
        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = setting_item($key, '');
        }

        return response()->json([
            'data' => [
                'group' => $groupId,
                'settings' => $settings,
            ],
        ]);
    }

    protected function updateSettingsGroup(Request $request, string $groupId): JsonResponse
    {
        if ($permission = $this->checkPermission()) {
            return $permission;
        }

        $group = $this->getSettingsGroup($groupId);
        if (!$group) {
            return response()->json(['message' => ucfirst($groupId) . ' settings group not found'], 404);
        }

        $keys = array_flip((array) ($group['keys'] ?? []));
        $saved = [];
        foreach ($request->all() as $key => $val) {
            if (!isset($keys[$key])) {
                continue;
            }
            setting_update_item($key, is_array($val) ? json_encode($val) : $val);
            $saved[$key] = $val;
        }

        return response()->json([
            'message' => ucfirst($groupId) . ' settings saved',
            'updated' => array_keys($saved),
        ]);
    }

    protected function getSettingsGroup(string $groupId): ?array
    {
        $pages = Settings::getSettingPages();
        foreach ($pages as $page) {
            if (($page['id'] ?? null) === $groupId) {
                return $page;
            }
        }

        return null;
    }

    protected function checkPermission(): ?JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        if (!method_exists($user, 'hasPermission') || !$user->hasPermission('setting_update')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return null;
    }
}
