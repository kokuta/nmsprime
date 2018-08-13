<?php

namespace App\Http\Controllers\Auth;

use Bouncer;
use App\{Ability, Role};
use Illuminate\Http\Request;
use App\Http\Controllers\BaseViewController;

class CustomAbilityController extends AbilityController
{

	/**
	 * Compose a Collection to use in Blade from non-Crud Abilities
	 *
	 * @return Collection|mixed
	 * @author Christian Schramm
	 */
	public static function view()
	{
		return Ability::custom()->get()
			->pluck('title', 'id')
			->map(function ($title) {
				return collect([
					'title' => $title,
					'localTitle' => BaseViewController::translate_label($title),
					'helperText' => trans('helper.' . $title),
				]);
			});
	}

	/**
	 * Updates the Abilities that are not explicitly bound to a model and some
	 * Helper Abilities (like "allow all", "view all"). It is bound to the
	 * Route "customAbility.update" and called via AJAX Requests.
	 *
	 * @param Request $request
	 * @return Collection|mixed
	 * @author Christian Schramm
	 */
	public function update(Request $requestData)
	{
		$role = Role::find($requestData->roleId);

		$changedIds = 	intval($requestData->id) ?
						collect($requestData->id) :
						collect($requestData->changed)->filter()->keys();

		$abilities = Ability::whereIn('id', $changedIds)->get();

		$this->register($requestData, $abilities, $role->name);

		return collect([
			'id' => intval($requestData->id) ? $requestData->id : $changedIds ,
			'roleAbilities' => $role->getAbilities()->custom(),
			'roleForbiddenAbilities' => $role->getForbiddenAbilities()->custom()
		])->toJson();
	}

	/**
	 * Registers the custom abilities with Bouncer and therefore Laravels Gate
	 * with respect to the "allow all" ability. Only changed Abilities are
	 * handled to increase the Performance.
	 *
	 * @param mixed $requestData
	 * @param string $roleName
	 * @param Collection|Ability $abilities
	 * @return void
	 * @author Christian Schramm
	 */
	private function register($requestData, $abilities, string $roleName)
	{
		foreach ($abilities as $ability) {

			if ($requestData->changed[$ability->id] && array_key_exists($ability->id, $requestData->roleAbilities))
				Bouncer::allow($roleName)->to($ability->name, $ability->entity_type);

			if ($requestData->changed[$ability->id] && !array_key_exists($ability->id, $requestData->roleAbilities))
				Bouncer::disallow($roleName)->to($ability->name, $ability->entity_type);

			if ($requestData->changed[$ability->id] && array_key_exists($ability->id, $requestData->roleForbiddenAbilities))
				Bouncer::forbid($roleName)->to($ability->name, $ability->entity_type);

			if ($requestData->changed[$ability->id] && !array_key_exists($ability->id, $requestData->roleForbiddenAbilities))
				Bouncer::unforbid($roleName)->to($ability->name, $ability->entity_type);

		}

		Bouncer::refresh();
	}
}
