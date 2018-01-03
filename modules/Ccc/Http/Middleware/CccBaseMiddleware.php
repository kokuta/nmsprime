<?php namespace Modules\Ccc\Http\Middleware;

use Closure;
use App\Exceptions\AuthExceptions;


class CccBaseMiddleware {

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		try {
			// no user logged in
			if (is_null(\Auth::guard('ccc')->user())) {
				throw new AuthExceptions('Login Required.');
			}
		}
		catch (AuthExceptions $ex) {
			/* return \View::make('auth.denied', array('error_msg' => $ex->getMessage())); */
			abort(403, $ex->getMessage());
		}

		return $next($request);
	}

}
