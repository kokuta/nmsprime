<?php

namespace App;

use Silber\Bouncer\Database\Concerns\IsAbility;

class Ability extends BaseModel
{
    use IsAbility;

    /**
     * Crud Actions Array, that is used to populate the Ability Blade and to
     * iterate through the various actions in Blade context. As key the
     * Shorthand for abilities is used. Value is an Option array of
     * Properties which are used only inside Blade context.
     *
     * @return Collection|string
     * @author Christian Schramm
     */
    public static function getCrudActions()
    {
        return collect([
                '*'  => ['name' => 'manage', 'icon' => 'fa-star', 'bsclass' => 'success'],
            'view'   => ['name' => 'view', 'icon' => 'fa-eye', 'bsclass' => 'info'],
            'create' => ['name' => 'create', 'icon' => 'fa-plus', 'bsclass' => 'primary'],
            'update' => ['name' => 'update', 'icon' => 'fa-pencil', 'bsclass' => 'warning'],
            'delete' => ['name' => 'delete', 'icon' => 'fa-trash', 'bsclass' => 'danger'],
        ]);
    }

	/**
	 * Helper method to keep the Code DRY. It checks if the ability
	 * is a custom Ability.
	 *
	 * @param Ability $ability
	 * @return boolean
	 * @author Christian Schramm
	 */
	public function isCustom()
	{
		return ($this->entity_type == '*' ||
				$this->entity_type == null ||
				! self::getCrudActions()->has($this->name));
	}

	/**
	 * Static mehtod to get all non-Crud Abilities.
	 *
	 * @return Collection|mixed
	 * @author Christian Schramm
	 */
	public static function custom()
	{
        return static::whereNotIn('name', self::getCrudActions()->keys())
            		->orWhere('entity_type', '*');
	}

	/**
	 * Filter Abilities and return the non-Crud ones. Parameter forbidden,
	 * determines if forbidden or allowed abilities are returned.
	 *
	 * @param QueryBuilder $query
	 * @param bool $forbidden
	 * @return QueryBuilder
	 * @author Christian Schramm
	 */
	public function scopeCustom($query, $forbidden = false)
	{
		return $query->where(function ($constraint) {
						$constraint->where('abilities.entity_type', '*')
								->orWhereNull('abilities.entity_type')
								->orWhereNotIn('abilities.name', self::getCrudActions()->keys());
					})
					->where('forbidden', $forbidden);
	}

	/**
	 * Get All Abilities and return only the non-Crud based ones.
	 *
	 * @param Ability $abilities
	 * @return Collection
	 * @author Christian Schramm
	 */
	public function scopeModel($query, $forbidden = false)
	{
		return $query->where(function ($constraint) {
						$constraint->where('abilities.entity_type', '!=','*')
								->whereNotNull('abilities.entity_type')
								->whereIn('abilities.name', self::getCrudActions()->keys());
					})
					->where('forbidden', $forbidden);
	}

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = [])
    {
        return new AbilitiesCollection($models);
    }

}
