<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Clients\CreateClient;
use App\Actions\Clients\DeleteClient;
use App\Actions\Clients\RotateClientSecret;
use App\Actions\Clients\ToggleClient;
use App\Actions\Clients\UpdateClient;
use App\Http\Controllers\Controller;
use App\Models\OAuthClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * Store a newly created OAuth client.
     */
    public function store(Request $request, CreateClient $createClient): RedirectResponse
    {
        $this->authorize('create', OAuthClient::class);

        $client = $createClient->create($request->only(['name', 'grant', 'redirect', 'confidential']));

        return redirect()->route('admin.clients.show', $client)->with('status', __('Client created.'));
    }

    /**
     * Update the given OAuth client.
     */
    public function update(Request $request, OAuthClient $client, UpdateClient $updateClient): RedirectResponse
    {
        $this->authorize('update', $client);

        $updateClient->update($client, $request->only(['name', 'redirect']));

        return redirect()->route('admin.clients.edit', $client)->with('status', __('Client updated.'));
    }

    /**
     * Rotate the given client secret.
     */
    public function rotate(OAuthClient $client, RotateClientSecret $rotateClientSecret): RedirectResponse
    {
        $this->authorize('rotate', $client);

        $rotateClientSecret->rotate($client);

        session(['client_secret' => $client->plainSecret]);

        return redirect()->route('admin.clients.show', $client)->with('status', __('Client secret rotated.'));
    }

    /**
     * Toggle the given client's status.
     */
    public function toggle(OAuthClient $client, ToggleClient $toggleClient): RedirectResponse
    {
        $this->authorize('toggle', $client);

        $toggleClient->toggle($client);

        return redirect()->route('admin.clients.index')->with('status', __('Client status updated.'));
    }

    /**
     * Delete (revoke) the given client and its tokens.
     */
    public function destroy(OAuthClient $client, DeleteClient $deleteClient): RedirectResponse
    {
        $this->authorize('delete', $client);

        $deleteClient->delete($client);

        return redirect()->route('admin.clients.index')->with('status', __('Client deleted.'));
    }
}
