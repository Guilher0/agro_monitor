<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: RemoveTenantDomainParameter
 *
 * Remove o parâmetro `{tenant_domain}` da rota ativa.
 * Isso evita que o Laravel passe o subdomínio/domínio do tenant como o primeiro argumento
 * em todas as assinaturas dos métodos dos Controllers, garantindo que o Route Model Binding
 * funcione perfeitamente com typehinting padrão (ex: edit(Asset $asset)).
 */
class RemoveTenantDomainParameter
{
    /**
     * Trata a requisição de entrada.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->route()) {
            $request->route()->forgetParameter('tenant_domain');
        }

        return $next($request);
    }
}
