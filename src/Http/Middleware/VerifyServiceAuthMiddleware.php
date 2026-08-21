<?php

namespace SocialDept\AtpClient\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use SocialDept\AtpClient\Auth\ServiceAuth;
use SocialDept\AtpClient\Exceptions\ServiceAuthException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires a valid AT Protocol service auth token on an inbound request.
 *
 *     Route::get('/xrpc/com.example.doThing', DoThingController::class)
 *         ->middleware('atp.service-auth:com.example.doThing');
 *
 * The argument is the NSID the token must be bound to. Pass it: a token minted
 * to call one endpoint should not be spendable at another, and that check only
 * happens if the route says what it is.
 *
 * On success the verified token is attached to the request, so a handler can
 * ask who is calling without re-parsing anything:
 *
 *     $request->attributes->get('atp_service_auth')->did();
 */
class VerifyServiceAuthMiddleware
{
    /**
     * The request attribute the verified token is stored under.
     */
    public const ATTRIBUTE = 'atp_service_auth';

    public function __construct(
        protected ServiceAuth $serviceAuth,
    ) {
    }

    /**
     * @param  string|null  $method  The NSID this route accepts tokens for
     */
    public function handle(Request $request, Closure $next, ?string $method = null): Response
    {
        $token = $this->serviceAuth->fromHeader($request->header('Authorization'));

        if ($token === null) {
            return $this->refuse(ServiceAuthException::missing());
        }

        try {
            $verified = $this->serviceAuth->verify($token, $this->audience(), $method);
        } catch (ServiceAuthException $e) {
            return $this->refuse($e);
        }

        $request->attributes->set(self::ATTRIBUTE, $verified);

        return $next($request);
    }

    /**
     * The service identifier this application answers to.
     *
     * Null skips the audience check, which is only safe when nothing else
     * distinguishes this service from another the caller could reach.
     */
    protected function audience(): ?string
    {
        $audience = config('atp-client.service_auth.audience');

        return is_string($audience) && $audience !== '' ? $audience : null;
    }

    /**
     * Refuse in the shape an XRPC client expects.
     */
    protected function refuse(ServiceAuthException $exception): Response
    {
        return response()->json([
            'error' => $exception->error,
            'message' => $exception->getMessage(),
        ], $exception->status);
    }
}
