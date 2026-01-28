<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\Facades\Blade; // <--- IMPORTANTE

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')

            // Branding
            ->brandName('ManagerISP')

            // 1. ORGANIZACIÓN: Orden de los grupos
            ->navigationGroups([
                'Escritorio', 
                'Clientes',
                'Configuración',
                'Sistema',
            ])
            // Hacer que los grupos se puedan cerrar/colapsar
            ->collapsibleNavigationGroups(true) 

            // Login propio de Filament
            ->login()

            // Colores minimalistas
            ->colors([
                'primary' => Color::Slate,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger'  => Color::Rose,
            ])

            ->sidebarCollapsibleOnDesktop()

            // 2. DISEÑO PREMIUM: Inyectar CSS personalizado
            ->renderHook(
                'panels::body.end',
                fn (): string => Blade::render(<<<HTML
                    <style>
                        /* Reducir espacio entre grupos (Compacto) */
                        .fi-sidebar-nav-groups {
                            gap: 0.5rem !important; 
                        }
                        
                        /* Reducir espacio entre items dentro del grupo */
                        .fi-sidebar-group-items {
                            gap: 0.2rem !important; 
                        }

                        /* ESTILO ACTIVO PREMIUM */
                        /* Fondo sutil y borde izquierdo de color */
                        .fi-sidebar-item-active a {
                            background-color: rgba(var(--primary-500), 0.08) !important;
                            border-left: 4px solid rgb(var(--primary-500));
                            border-radius: 0 0.5rem 0.5rem 0; /* Bordes redondeados solo a la derecha */
                            font-weight: 600 !important;
                        }
                        
                        /* Ajuste de icono activo para que combine */
                        .fi-sidebar-item-active a svg {
                            color: rgb(var(--primary-500)) !important;
                        }

                        /* Fuente más nítida y profesional */
                        .fi-sidebar-item-label {
                            font-size: 0.9rem !important;
                            letter-spacing: 0.01em;
                        }

                        /* Títulos de grupos más discretos */
                        .fi-sidebar-group-label {
                            font-size: 0.75rem !important;
                            text-transform: uppercase;
                            letter-spacing: 0.05em;
                            font-weight: 700;
                            opacity: 0.7;
                        }
                    </style>
                HTML)
            )

            // Auto-descubrimiento
            ->discoverResources(
                in: app_path('Filament/Resources'),
                for: 'App\\Filament\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Pages'),
                for: 'App\\Filament\\Pages'
            )
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(
                in: app_path('Filament/Widgets'),
                for: 'App\\Filament\\Widgets'
            )
            ->widgets([
                Widgets\AccountWidget::class,
            ])

            // Middleware web completo
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])

            // Protección del panel (obligatorio)
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}