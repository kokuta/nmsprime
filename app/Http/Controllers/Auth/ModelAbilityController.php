<?php

namespace App\Http\Controllers\Auth;

use Bouncer, Module, Str;
use App\{Ability, BaseModel, Role};
use Illuminate\Http\Request;
use App\Http\Controllers\BaseViewController;

class ModelAbilityController extends AbilityController
{

	/**
	 * Updates the Abilities that are explicitly bound to a model with the CRUD
	 * actions manage (allow everything on that model), view, create, update
	 * and delete. It is bound to the Route "modelAbility.update" and is
	 * called via AJAX Requests.
	 *
	 * @param Request $request
	 * @return json|string
	 * @author Christian Schramm
	 */
	public function update(Request $request)
	{
		$requestData = collect($request->all())->forget('_token');

		$module = $requestData->pull('module');
		$allowAll = $requestData->pull('allowAll');
		$role = Role::find($requestData->pull('roleId'));

		$modelAbilities = self::getAll($role)[$module]
			->mapWithKeys(function ($actions, $model) use ($requestData) {
				if (!$requestData->has($model))
					$requestData[$model] = [];

				return [$model => $requestData[$model]];
			})
			->merge($requestData);

		$this->register($role, $modelAbilities, $allowAll);

		return self::getAll($role)->toJson();
    }

	/**
	 * Compose a Collection of all CRUD Abilities, which can be used to scaffold
	 * the Blade. Some Abilities are Grouped by Custom Rules, but mostly the
	 * Module Context is used. The Grouping was done to increase the UX.
	 *
	 * @param Role $role
	 * @return Collection|mixed
	 * @author Christian Schramm
	 */
	public static function getAll(Role $role)
	{
		$modelsToExclude = [
			'Dashboard',
		];

		$modules = Module::collections()->keys();
		$models = collect(BaseModel::get_models())->forget($modelsToExclude);

		$allowedAbilities = $role->getAbilities();
		$isAllowAllEnabled = $allowedAbilities->where('title', 'All abilities')->first();

		$abilities = $isAllowAllEnabled ?
					$role->getForbiddenAbilities()->model() :
					$allowedAbilities->model();

		$allAbilities = Ability::whereIn('id', $abilities->keys())->orderBy('id', 'asc')->get();

		//$forbidden = !! $role->abilities()->custom()->where('title', 'All abilities')->first();
		//$allAbilities = $role->abilities()->model($forbidden)->orderBy('id', 'asc')->get();

		// Grouping GlobalConfig, Authentication and HFC Permissions to increase usability
		$modelAbilities = collect([
			'GlobalConfig' => collect([
				'GlobalConfig','BillingBase','Ccc','HfcBase','ProvBase','ProvVoip','GuiLog'
				])->mapWithKeys(function ($name) use ($models, $allAbilities) {
					return self::getModelActions($name, $models, $allAbilities);
				})
		]);

		$modelAbilities['Authentication'] = self::filterAndMapModelActions('App', $models, $allAbilities);
		$modelAbilities['HFC'] = self::filterAndMapModelActions('Hfc', $models, $allAbilities);

		foreach ($modules as $module)
			$modelAbilities[$module] = self::filterAndMapModelActions($module, $models, $allAbilities);

		$modelAbilities = $modelAbilities->reject(function ($module) {
			return $module->isEmpty();
		});

		return $modelAbilities;
    }


	/**
	 * Helper method to keep the Code DRY. It filters for Models from a chosen
	 * Module and then maps the CRUD Abilities to each Model. There are some
	 * sensible defaults to make the Ability UI cleaner.
	 *
	 * @param String $name
	 * @param Collection $models
	 * @param Collection $allAbilities
	 * @return Collection
	 * @author Christian Schramm
	 */
	protected static function filterAndMapModelActions($name, $models, $allAbilities)
	{
		return $models->filter(function ($class) use ($name) {
			if ($name == 'App')
				return Str::contains($class, 'App' . '\\');

			if ($name == 'Hfc')
				return Str::contains($class, '\\' . 'Hfc');

			return Str::contains($class, '\\'. $name . '\\');
		})
		->mapWithKeys(function ($class, $name) use ($models, $allAbilities) {
			return self::getModelActions($name, $models, $allAbilities);
		});
	}

	/**
	 * Helper method to keep the Code DRY. Builds the Action Array for each Model.
	 *
	 * @param String $name
	 * @param Collection $models
	 * @param Collection $allAbilities
	 * @return Array
	 * @author Christian Schramm
	 */
	protected static function getModelActions($name, $models, $allAbilities)
	{
		return [
			$name => $allAbilities
					->where('entity_type', $name == 'Role' ? 'roles' : $models->pull($name)) // Bouncer specific
					->pluck('name')
			];
	}

	/**
	 * Registers the model CRUD abilities with Bouncer and therefore Laravels
	 * Gate with respect to the "allow all" ability. Only changed Abilities
	 * are handled to increase the Performance.
	 *
	 * @param Role $role
	 * @param Collection|mixed $modelAbilities
	 * @param mixed $allowAll
	 * @return void
	 * @author Christian Schramm
	 */
	private function register(Role $role, $modelAbilities, $allowAll)
	{
		$models = collect(BaseModel::get_models());
		$crudActions = Ability::getCrudActions();

		foreach ($modelAbilities as $model => $actions) {
			foreach ($actions as $action) {
				$crudActions->forget($action);
				$permissions = 	$allowAll  == 'true' && $allowAll != 'undefined' ?
							collect(['disallow', 'forbid']) :
							collect(['unforbid', 'allow']);

				$permissions->each(function($permission) use ($action, $role, $models, $model) {
					if ($action == '*')
						return Bouncer::$permission($role->name)->toManage($models[$model]);

					return Bouncer::$permission($role->name)->to($action, $models[$model]);
				});
			}

			foreach ($crudActions as $action => $options) {
				if ($action == '*') {
					Bouncer::disallow($role->name)->toManage($models[$model]);
					Bouncer::unforbid($role->name)->toManage($models[$model]);
					continue;
				}

				Bouncer::disallow($role->name)->to($action, $models[$model]);
				Bouncer::unforbid($role->name)->to($action, $models[$model]);
			}
		}

		Bouncer::refresh();
    }

}
