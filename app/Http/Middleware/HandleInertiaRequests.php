<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            // Standard shared data
            'auth' => function () use ($request) {
                return [
                    'user' => $request->user() ? [
                        'id' => $request->user()->id,
                        'name' => $request->user()->fullname,
                        'email' => $request->user()->email,
                        'avatar' => $request->user()->getFilamentAvatarUrl(),
                    ] : null,
                ];
            },
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'error' => fn () => $request->session()->get('error'),
                'success' => fn () => $request->session()->get('success'),
            ],
            'currentUrl' => url()->current(),
            
            // Complete meta data matching app.blade.php
            'meta' => [
                'title' => config('app.name'),
                'description' => config('app.description', 'PKKI ITERA Application'),
                'keywords' => config('app.keywords', 'laravel,inertia,react,filament,itera'),
                'author' => config('app.author', 'PKKI ITERA Team'),
                'robots' => 'index, follow',
                
                // Open Graph
                'og_title' => config('app.name', 'PKKI ITERA'),
                'og_description' => config('app.description', 'PKKI ITERA Application'),
                'og_image' => asset('images/og-image.jpg'),
                
                // Twitter
                'twitter_title' => config('app.name', 'PKKI ITERA'),
                'twitter_description' => config('app.description', 'PKKI ITERA Application'),
                'twitter_image' => asset('images/twitter-image.jpg'),
            ],
        ];
    }
}