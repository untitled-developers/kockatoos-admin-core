<?php

namespace UntitledDevelopers\KockatoosAdminCore\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use UntitledDevelopers\KockatoosAdminCore\Facades\Auth;
use UntitledDevelopers\KockatoosAdminCore\Models\Admin;
use UntitledDevelopers\KockatoosAdminCore\Services\MfaService;

class MfaController extends Controller
{
    protected MfaService $mfaService;

    public function __construct(MfaService $mfaService)
    {
        $this->mfaService = $mfaService;
    }

    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:admins,id'
        ]);

        $user = Admin::findOrFail($request->input('user_id'));
        $mfaEnabled = $this->mfaService->toggle($user);

        $response = [
            'message' => $mfaEnabled ? 'MFA enabled successfully' : 'MFA disabled successfully',
            'mfa_enabled' => $mfaEnabled
        ];

        if ($mfaEnabled) {
            $response['qr_code'] = $this->mfaService->getQrCodeImage($user);
        }

        return response()->json($response);
    }

    public function getQrCode(Request $request): JsonResponse
    {
        $user = Auth::user();
        $qrCode = $this->mfaService->getQrCodeImage($user);

        if (!$qrCode) {
            return response()->json(['error' => 'MFA not enabled'], 404);
        }

        return response()->json(['qr_code' => $qrCode]);
    }

    public function hasMfa(Request $request): JsonResponse
    {
        $request->validate([
            'identifier' => 'required|string'
        ]);

        $identifierField = config('login.identifier');

        $user = Admin::where($identifierField, $request->input('identifier'))->first();

        if (!$user) {
            return response()->json(['has_mfa' => false]);
        }

        return response()->json(['has_mfa' => $this->mfaService->hasMfa($user)]);
    }
}
