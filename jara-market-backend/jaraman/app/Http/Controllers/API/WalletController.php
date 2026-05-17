<?php

namespace App\Http\Controllers\API;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\FundWalletRequest;
use App\Http\Requests\Paystack\TransferToBankRequest;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\WalletResource;
use App\Services\WalletService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class WalletController extends Controller
{
    public function __construct(protected WalletService $walletService) {}

    /*
    |--------------------------------------------------------------------------
    | GET /jaram/wallet
    |--------------------------------------------------------------------------
    */
    #[OA\Get(
        path: "/api/wallet/balance",
        summary: "Get Wallet Balance",
        description: "Retrieve the authenticated user's wallet balance and status.",
        tags: ["Customer", "Vendor"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Wallet retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Wallet not found")
        ]
    )]
    public function balance(Request $request): JsonResponse
    {
        try {
            $wallet = $request->user()->wallet;

            if (! $wallet) {
                return response()->errorResponse('Wallet not found.', [], 404);
            }

            return response()->success('Wallet retrieved', new WalletResource($wallet));
        } catch (Exception $e) {
            report($e);

            return response()->errorResponse('Unable to retrieve wallet.', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /jaram/payments/initialize-transaction
    | Body: { amount, currency, callback_url, payment_gateway, metadata? }
    |--------------------------------------------------------------------------
    */
    #[OA\Post(
        path: "/api/wallet/fund",
        summary: "Initialize Wallet Funding",
        description: "Initialize a transaction to add funds to the wallet via Paystack.",
        tags: ["Customer", "Vendor"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["amount"],
                properties: [
                    new OA\Property(property: "amount", type: "number", example: 2000.00),
                    new OA\Property(property: "callback_url", type: "string", example: "https://example.com/callback")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Funding initialized"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
    public function initializeFunding(FundWalletRequest $request): JsonResponse
    {
        try {
            $url = $this->walletService->initializeFunding(
                $request->user(),
                $request->validated()
            );

            return response()->success('Payment initialized. Redirect user to the URL to complete payment.', [
                'url' => $url,
            ]);
        } catch (GeneralException $e) {
            return response()->errorResponse($e->getMessage(), [], $e->getCode() ?: 400);
        } catch (Exception $e) {
            report($e);

            return response()->errorResponse('Unable to initialize payment.', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /jaram/wallet/transfer-to-bank
    | Body: { amount, bank_id, currency?, remark? }
    |--------------------------------------------------------------------------
    */
    #[OA\Post(
        path: "/api/wallet/withdraw",
        summary: "Withdraw to Bank",
        description: "Initiate a transfer from wallet to a verified bank account.",
        tags: ["Customer", "Vendor"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["amount", "bank_id"],
                properties: [
                    new OA\Property(property: "amount", type: "number", example: 5000.00),
                    new OA\Property(property: "bank_id", type: "integer", example: 1),
                    new OA\Property(property: "remark", type: "string", example: "Pocket money")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Withdrawal initiated"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Insufficient balance or Validation error")
        ]
    )]
    public function transferToBank(TransferToBankRequest $request): JsonResponse
    {
        try {
            $result = $this->walletService->transferToBank(
                $request->user(),
                $request->validated()
            );

            return response()->success('Withdrawal initiated. Funds will arrive within a few minutes.', $result);
        } catch (GeneralException $e) {
            return response()->errorResponse($e->getMessage(), [], $e->getCode() ?: 400);
        } catch (Exception $e) {
            report($e);

            return response()->errorResponse('Unable to process withdrawal. Please try again.', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /jaram/wallet/transactions?type=credit|debit&per_page=20
    |--------------------------------------------------------------------------
    */
    #[OA\Get(
        path: "/api/wallet/transactions",
        summary: "Transaction History",
        description: "Retrieve a list of all wallet transactions (credits and debits).",
        tags: ["Customer", "Vendor"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "type", in: "query", description: "Filter by type (credit, debit)", schema: new OA\Schema(type: "string", enum: ["credit", "debit"])),
            new OA\Parameter(name: "per_page", in: "query", description: "Items per page", schema: new OA\Schema(type: "integer", default: 20))
        ],
        responses: [
            new OA\Response(response: 200, description: "Transactions retrieved successfully"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function transactions(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'nullable|in:credit,debit',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $logs = $this->walletService->getTransactionHistory(
                user    : $request->user(),
                perPage : (int) $request->get('per_page', 20),
                type    : $request->get('type'),
            );

            return response()->success(
                'Transaction history retrieved.',
                TransactionResource::collection($logs)->response()->getData(true)
            );
        } catch (Exception $e) {
            report($e);

            return response()->errorResponse('Unable to retrieve transactions.', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
