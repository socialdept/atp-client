<?php

namespace SocialDept\AtpClient\Enums;

/**
 * Why a sign-out could not revoke the session at the server. The session is
 * forgotten locally either way.
 */
enum RevocationFailure: string
{
    case NoSession = 'no_session';                 // nothing stored for the DID
    case NoEndpoint = 'no_endpoint';               // the server advertises no revocation_endpoint
    case MissingDpopKey = 'missing_dpop_key';      // the key the tokens are bound to is gone
    case Refused = 'refused';                      // the server answered with an error
    case Unreachable = 'unreachable';              // network failure, failed discovery, a 5xx or a 429
}
