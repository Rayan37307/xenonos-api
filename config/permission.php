<?php

return [

    'models' => [

        /*
         * When using the "HasPermissions" trait from this package, we need to know which
         * Eloquent model should be used to retrieve your permissions. Of course, it
         * is often just the "Permission" model but you may use whatever you like.
         *
         * The model you want to use as a Permission model needs to implement the
         * `Spatie\Permission\Contracts\Permission` contract.
         */

        'permission' => Spatie\Permission\Models\Permission::class,

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * Eloquent model should be used to retrieve your roles. Of course, it
         * is often just the "Role" model but you may use whatever you like.
         *
         * The model you want to use as a Role model needs to implement the
         * `Spatie\Permission\Contracts\Role` contract.
         */

        'role' => Spatie\Permission\Models\Role::class,

    ],

    'table_names' => [

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * table should be used to retrieve your roles. We have chosen a basic
         * default value but you may easily change it to any table you like.
         */

        'roles' => 'roles',

        /*
         * When using the "HasPermissions" trait from this package, we need to know which
         * table should be used to retrieve your permissions. We have chosen a basic
         * default value but you may easily change it to any table you like.
         */

        'permissions' => 'permissions',

        /*
         * When using the "HasPermissions" trait from this package, we need to know which
         * table should be used to retrieve your models permissions. We have chosen a
         * basic default value but you may easily change it to any table you like.
         */

        'model_has_permissions' => 'model_has_permissions',

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * table should be used to retrieve your models roles. We have chosen a
         * basic default value but you may easily change it to any table you like.
         */

        'model_has_roles' => 'model_has_roles',

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * table should be used to retrieve your roles permissions. We have chosen a
         * basic default value but you may easily change it to any table you like.
         */

        'role_has_permissions' => 'role_has_permissions',
    ],

    'column_names' => [
        /*
         * Change this if you want to name the related pivots other than defaults
         */
        'role_pivot_key' => 'role_id', //default 'role_id'
        'permission_pivot_key' => 'permission_id', //default 'permission_id'

        /*
         * Change this if you want to name the related model primary key other than
         * `model_id`.
         *
         * For example, this would be nice if your primary keys are all UUIDs. In
         * that case, name this `model_uuid`.
         */

        'model_morph_key' => 'model_id',

        /*
         * Change this if you want to use the teams feature and your related model's
         * foreign key is other than `team_id`.
         */

        'team_foreign_key' => 'team_id',
    ],

    /*
     * When using the "HasRoles" trait from this package, we need to know which
     * table should be used to retrieve your objects permissions. We have chosen a
     * basic default value but you may easily change it to any table you like.
     */

    'permission_table' => 'permissions',

    /*
     * When using the "HasRoles" trait from this package, we need to know which
     * method should be used to retrieve your objects roles. We have chosen a
     * basic default value but you may easily change it to any method you like.
     */

    'role_table' => 'roles',

    /*
     * When using the "HasRoles" trait from this package, we need to know which
     * method should be used to retrieve your objects permissions. We have chosen a
     * basic default value but you may easily change it to any method you like.
     */

    'permission_morph_key' => 'model_id',

    /*
     * When using the "HasRoles" trait from this package, we need to know which
     * method should be used to retrieve your objects roles. We have chosen a
     * basic default value but you may easily change it to any method you like.
     */

    'role_morph_key' => 'model_id',

    /*
     * Set this to `true` if you want to use teams feature.
     */

    'teams' => false,

    /*
     * You may set this to `true` if you want to use the permission registration
     * feature.
     */

    'register_permission_check_method' => true,

    /*
     * You may set this to `true` if you want to use the role registration
     * feature.
     */

    'register_role_check_method' => true,

    /*
     * You may set this to `true` if you want to use the teams feature and your
     * models should be separated by team.
     */

    'use_teams_feature' => false,

    /*
     * By default, the cache stores the permissions and roles for each user. This
     * is for performance optimization. If you want to disable this, set this to false.
     */

    'cache_enabled' => true,

    /*
     * By default, the cache key is generated using the model's primary key and the
     * model's class name. If you want to customize the cache key, you can set a
     * custom key here.
     */

    'cache_key' => 'spatie.permission.cache',

    /*
     * You may set this to a positive number (in seconds) to specify how long the
     * cache should last. By default, the cache lasts forever.
     */

    'cache_expiration_time' => 60 * 24,

    /*
     * When using the "HasRoles" trait from this package, we need to know which
     * guard should be used to retrieve your roles/permissions. This must be the
     * same guard name as defined in your config/auth.php file.
     */

    'default_guard' => null,
];
