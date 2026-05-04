<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentLog;
use App\Services\PaymentGateways\Paystack;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentApiController extends Controller
{
    public function __construct(protected Paystack $paystack) {}

    /*
    |--------------------------------------------------------------------------
    | POST /api/payments/initiate
    | Body: { order_id, amount, email? }
    | Returns: { authorization_url, reference }
    |--------------------------------------------------------------------------
    */
    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'amount'   => 'required|numeric|min:1',
            'email'    => 'nullable|email',
        ]);

        try {
            $user   = $request->user();
            $order  = Order::where('user_id', $user->id)->findOrFail($validated['order_id']);
            $email  = $validated['email'] ?? $user->email;
            $amount = $validated['amount']; // service converts to kobo internally

            // Set initiator + owner on the Paystack service before calling initialize
            $result = $this->paystack
                ->setTransactionInitiator($user)
                ->setTransactionOwner($order)
                ->initializeTransaction(
                    email    : $email,
                    amount   : $amount,
                    currency : 'NGN',
                    metadata : ['order_id' => $order->id, 'user_id' => $user->id]
                );

            return response()->json([
                'status'  => true,
                'message' => 'Payment initialized. Redirect user to authorization_url.',
                'data'    => [
                    'authorization_url' => $result['authorization_url'] ?? null,
                    'reference'         => $result['reference'] ?? null,
                    'access_code'       => $result['access_code'] ?? null,
                ],
            ], 200);
        } catch (Exception $e) {
            report($e);
            return response()->json([
                'status'  => false,
                'message' => 'Payment initialization failed: ' . $e->getMessage(),
                'data'    => [],
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /api/payments/verify
    | Body: { reference }
    | Confirms with Paystack, updates order to 'paid'
    |--------------------------------------------------------------------------
    */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference' => 'required|string',
        ]);

        try {
            $data = $this->paystack->verifyTransaction($validated['reference']);

            // Paystack service stores logs under 'txn_ref' column
            $paymentLog = PaymentLog::where('txn_ref', $validated['reference'])->first();

            if ($paymentLog) {
                $paymentLog->update(['status' => 'success']);

                // Update the related order status to 'paid'
                $ownerId   = $paymentLog->transaction_owner_id ?? null;
                $ownerType = $paymentLog->transaction_owner_type ?? null;

                if ($ownerId && $ownerType === (new Order)->getMorphClass()) {
                    $order = Order::find($ownerId);
                    if ($order) {
                        $order->update(['status' => 'paid']);
                    }
                }
            }

            return response()->json([
                'status'  => true,
                'message' => 'Payment verified successfully',
                'data'    => [
                    'reference' => $validated['reference'],
                    'amount'    => isset($data['amount']) ? $data['amount'] / 100 : null,
                    'paid_at'   => $data['paid_at'] ?? now()->toDateTimeString(),
                ],
            ], 200);
        } catch (Exception $e) {
            report($e);
            return response()->json([
                'status'  => false,
                'message' => 'Payment verification failed: ' . $e->getMessage(),
                'data'    => [],
            ], 400);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /api/payments/history
    |--------------------------------------------------------------------------
    */
    public function history(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $payments = PaymentLog::where('transaction_initiator_id', $user->id)
                ->where('transaction_initiator_type', get_class($user))
                ->where('status', 'success')
                ->latest()
                ->paginate((int) $request->get('per_page', 20));

            return response()->json([
                'status'  => true,
                'message' => 'Payment history retrieved successfully',
                'data'    => $payments->items(),
                'meta'    => [
                    'total'        => $payments->total(),
                    'current_page' => $payments->currentPage(),
                    'last_page'    => $payments->lastPage(),
                ],
            ], 200);
        } catch (Exception $e) {
            report($e);
            return response()->json(['status' => false, 'message' => 'Failed to retrieve payment history'], 500);
        }
    }
}
