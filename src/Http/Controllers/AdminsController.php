<?php

namespace UntitledDevelopers\KockatoosAdminCore\Http\Controllers;

use UntitledDevelopers\KockatoosAdminCore\Http\Controllers\CRUD\CrudController;
use UntitledDevelopers\KockatoosAdminCore\Http\Controllers\CRUD\SearchableField;
use UntitledDevelopers\KockatoosAdminCore\Http\Controllers\CRUD\SearchTypes;
use UntitledDevelopers\KockatoosAdminCore\Models\Admin;
use UntitledDevelopers\KockatoosAdminCore\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JetBrains\PhpStorm\Pure;
use UntitledDevelopers\KockatoosAdminCore\Models\Role;

class AdminsController extends CrudController
{
    protected string $table = 'admins';
    protected string $modelClass = Admin::class;
    protected string $filesDirectory = 'admins';
    protected array $searchFields;
    protected bool $safeDelete = false;
    protected array $selectColumns = [
        'admins.id',
        'admins.name',
        'admins.phone',
        'admins.username',
        'admins.is_locked',
        'admins.created_at',
        'admins.updated_at'
    ];

    #[Pure]
    public function __construct()
    {
        $this->searchFields = [
            SearchableField::create('admins.id', SearchTypes::$EXACT),
            SearchableField::create('admins.name', SearchTypes::$CONTAINS),
            SearchableField::create('admins.phone', SearchTypes::$CONTAINS),
            SearchableField::create('admins.username', SearchTypes::$CONTAINS)
        ];
    }

    protected function saveModel(Request $request, BaseModel $model, bool $isNew): BaseModel
    {
        //TODO add validation logic later
        $data = $this->initSaveModel($request, $model);
        $model->name = $data->name;
        $model->username = $data->username;
        $model->phone = $data->phone;
        if (isset($data->password) && ($data->password != null || $data->password != '')) {
            $model->password = bcrypt($data->password);
        }
        $model->save();

        if (isset($data->roles) && is_array($data->roles)) {
            $model->roles()->sync($data->roles);
        }

        return $model;
    }

    protected function builder(): Builder
    {
        return parent::builder()
            ->with(['roles']);
    }

    public function toggleLocked($id): JsonResponse
    {
        $model = $this->getModel($id);
        $model->is_locked = !$model->is_locked;
        $model->save();
        return response()->json($this->getModel($id));
    }

    public function getFormData(): JsonResponse
    {
        $roles = Role::query()->select(['id', 'name', 'display_name'])->get();
        $data = [
            'roles' => $roles
        ];
        return response()->json($data);
    }


}
