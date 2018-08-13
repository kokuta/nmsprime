<?php

namespace App;

use Illuminate\Database\Eloquent\Collection;

class AbilitiesCollection extends Collection
{
	/**
	 * Get All Abilities and return only the non-Crud based ones.
	 *
	 * @param Ability $abilities
	 * @return Collection|mixed
	 * @author Christian Schramm
	 */
	public function custom()
	{
		return $this->filter(function($ability) {
					return $ability->isCustom();
				})
				->pluck('title', 'id');
	}

	/**
	 * Get All Abilities and return only the Crud based Abilities.
	 *
	 * @param Ability $abilities
	 * @return Collection|mixed
	 * @author Christian Schramm
	 */
	public function model()
	{
		return $this->filter(function($ability) {
					return ! $ability->isCustom();
				})
				->map(function ($ability) {
					return ['id' => $ability->id,
							'name' => $ability->name,
							'entity_type' => $ability->entity_type
					];
				})
				->keyBy('id');
	}
}



