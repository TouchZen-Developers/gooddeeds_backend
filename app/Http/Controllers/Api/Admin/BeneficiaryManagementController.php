<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BeneficiaryApproveRequest;
use App\Http\Requests\Admin\BeneficiaryRejectRequest;
use App\Mail\BeneficiaryApprovedMail;
use App\Mail\BeneficiaryRejectedMail;
use App\Models\Beneficiary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class BeneficiaryManagementController extends Controller
{
    /**
     * Get all beneficiaries (with optional status filter)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Beneficiary::with('user:id,name,first_name,last_name,email,phone_number,role');

        // Filter by status if provided
        if ($request->has('status')) {
            $status = $request->input('status');
            if (in_array($status, ['pending', 'approved', 'rejected'])) {
                $query->where('status', $status);
            }
        }

        $beneficiaries = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $beneficiaries,
        ]);
    }

    /**
     * Get a specific beneficiary
     */
    public function show(Beneficiary $beneficiary): JsonResponse
    {
        $beneficiary->load('user:id,name,first_name,last_name,email,phone_number,role,created_at');

        return response()->json([
            'success' => true,
            'data' => [
                'beneficiary' => $beneficiary,
            ],
        ]);
    }

    /**
     * Approve a beneficiary application
     */
    public function approve(BeneficiaryApproveRequest $request, Beneficiary $beneficiary): JsonResponse
    {
        // Check if already approved
        if ($beneficiary->isApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'This beneficiary has already been approved.',
            ], 400);
        }

        // Approve the beneficiary
        $beneficiary->approve();

        // Send approval email
        try {
            Mail::to($beneficiary->user->email)->send(new BeneficiaryApprovedMail($beneficiary));
            $emailSent = true;
        } catch (\Exception $e) {
            $emailSent = false;
        }

        return response()->json([
            'success' => true,
            'message' => 'Beneficiary approved successfully.',
            'data' => [
                'beneficiary' => $beneficiary->fresh(),
                'email_sent' => $emailSent,
            ],
        ]);
    }

    /**
     * Reject a beneficiary application
     */
    public function reject(BeneficiaryRejectRequest $request, Beneficiary $beneficiary): JsonResponse
    {
        // Check if already rejected
        if ($beneficiary->isRejected()) {
            return response()->json([
                'success' => false,
                'message' => 'This beneficiary has already been rejected.',
            ], 400);
        }

        $reason = $request->input('reason', '');

        // Reject the beneficiary
        $beneficiary->reject();

        // Send rejection email
        try {
            Mail::to($beneficiary->user->email)->send(new BeneficiaryRejectedMail($beneficiary, $reason));
            $emailSent = true;
        } catch (\Exception $e) {
            $emailSent = false;
        }

        return response()->json([
            'success' => true,
            'message' => 'Beneficiary rejected successfully.',
            'data' => [
                'beneficiary' => $beneficiary->fresh(),
                'email_sent' => $emailSent,
            ],
        ]);
    }

    /**
     * Get statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total' => Beneficiary::count(),
            'pending' => Beneficiary::pending()->count(),
            'approved' => Beneficiary::approved()->count(),
            'rejected' => Beneficiary::rejected()->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
