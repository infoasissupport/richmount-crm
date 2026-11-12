<?php

namespace App\Http\Controllers;

use App\Models\CompanyCard;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompanyCardController extends Controller
{
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        if (! $user || (! $user->can('expenses.create') && ! $user->can('expenses.approve'))) {
            abort(403);
        }

        $validated = $request->validate([
            'card_name' => ['required', 'string', 'max:100'],
            'last_four' => ['nullable', 'string', 'max:4', 'regex:/^\d{0,4}$/'],
        ]);

        $card = CompanyCard::create([
            'card_name' => trim($validated['card_name']),
            'last_four' => isset($validated['last_four']) && trim($validated['last_four']) !== ''
                ? trim($validated['last_four'])
                : null,
            'is_active' => true,
        ]);

        AuditService::log(
            'created',
            $card,
            'Added company card ' . $card->displayLabel() . '.',
            null,
            $card->toArray()
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'card' => [
                    'id' => $card->id,
                    'card_name' => $card->card_name,
                    'last_four' => $card->last_four,
                    'label' => $card->displayLabel(),
                ],
            ], 201);
        }

        return back()->with('success', 'Company card added.');
    }
}
