<?php

namespace gem\middleware\csp;

// Interface McFacey
use gem\interfaces\middleware\middlewareInterface as middlewareInterface;
use ParagonIE\CSPBuilder\CSPBuilder;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;


/**
 * Middleware to ensure that the Group Routes abide by the Content Security Policy
 * @author Paul Berry <paulb@glasgows.co.uk>
 * @link https://glagsows.co.uk
 */

class csp implements middlewareInterface {

    const CSP_HEADER_ENABLE = 'Content-Security-Policy';
    const CSP_HEADER_REPORT_ONLY = 'Content-Security-Policy-Report-Only';

    private $cspBuilder;
    private $reportOnly;



	/**
	 * @param Request        $request
	 * @param RequestHandler $handler
	 * @throws \Exception
	 */
    public function __construct()
    {
    
        // set up
	    $configs = file_get_contents('../src/configs/csp.json');
        $this->cspBuilder = CSPBuilder::fromData($configs);

    }



	/**
	 * Check for content security policy
	 *
	 * @param Request        $request PSR-7 request
	 * @param RequestHandler $handler PSR-15 request handler
	 *
	 * @throws \Exception
	 */
    public function __invoke(Request $request, RequestHandler $handler)
    {
    	$this->cspBuilder->sendCSPHeader();

	    $response = $handler->handle($request);


	    return $response;

    }
}
