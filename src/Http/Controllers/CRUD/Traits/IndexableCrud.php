<?php


namespace UntitledDevelopers\KockatoosAdminCore\Http\Controllers\CRUD\Traits;

use UntitledDevelopers\KockatoosAdminCore\Http\Controllers\CRUD\SearchableField;
use UntitledDevelopers\KockatoosAdminCore\Http\Controllers\CRUD\SearchTypes;
use UntitledDevelopers\KockatoosAdminCore\Models\BaseModel;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

trait IndexableCrud
{
    protected int $defaultPerPage = 10;

    /**
     * @var array<SearchableField>
     */
    protected array $searchFields = [];

    /**
     * @var array
     */
    protected array $selectColumns = [];

    protected bool $safeDelete = true;

    public function index(Request $request): JsonResponse
    {
        $builder = $this->initializeBuilder($this->builder());

        $builder = $builder->orderBy($this->table . '.id', 'desc');
        $perPage = (int)$request->input('perPage');
        $output = $perPage === -1
            ? $builder->get()
            : $builder->paginate($perPage != 0 ? $perPage : $this->defaultPerPage);
        return response()->json($output);
    }

    protected function initializeBuilder(Builder $builder): Builder
    {
        if (request()->input('searchFor')) {
            $builder = $builder->where(
                function (Builder $query) {
                    foreach ($this->searchFields as $field) {
                        $query = $this->getSearchQuery($query, $field, request()->input('searchFor'));
                    }
                });
        }

        if (request()->input('sortBy') && request()->input('sortAs')) {
            $builder = $builder->orderBy(request()->input('sortBy'), request()->input('sortAs'));
        }

        // Get order by and filter by
        if (request()->has('orderByFields')) {
            $orderByFields = \request()->input('orderByFields');
            foreach ($orderByFields as $orderByField) {
                $builder = $builder->orderBy($orderByField['field'], $orderByField['direction']);
            }
        }

        if (request()->has('filterByFields')) {
            $filterByFields = \request()->input('filterByFields');
            foreach ($filterByFields as $filterByField) {
                if ($filterByField['operator'] == 'nullable') {
                    if (!isset($filterByField['value'])) {
                        $builder = $builder->whereNull($filterByField['field']);
                        continue;
                    } else {
                        $filterByField['operator'] = '=';
                    }
                }
                if ($filterByField['operator'] == 'like') {
                    $builder = $builder->where($filterByField['field'], 'LIKE', '%' . $filterByField['value'] . '%');
                } else if ($filterByField['operator'] == 'date_range') {
                    $startDate = Carbon::parse($filterByField['value'][0])->setTimezone(config('app.timezone'))->startOfDay()->format('Y-m-d H:i:s');
                    $endDate = Carbon::parse($filterByField['value'][1] ?? $filterByField['value'][0])->setTimezone(config('app.timezone'))->endOfDay()->format('Y-m-d H:i:s');
                    $builder = $builder->whereDate($filterByField['field'], '>=', $startDate);
                    $builder = $builder->whereDate($filterByField['field'], '<=', $endDate);

                } else {
                    $builder = $builder->where($filterByField['field'], $filterByField['operator'], $filterByField['value']);
                }
            }
        }

        return $builder;
    }

    protected function builder(): Builder
    {
        $builder = BaseModel::query()->setModel(new $this->modelClass)->from($this->table);
        if ($this->safeDelete) {
            $builder = $builder->whereNull($this->table . '.deleted_at');
        }

        return $this->select($builder);
    }

    protected function select(Builder $builder): Builder
    {
        if (count($this->selectColumns) == 0) {
            return $builder->select('*');
        }
        return $builder->select($this->selectColumns);
    }

    /**
     * @throws Exception
     */
    private function getSearchQuery(Builder $query, SearchableField $field, $searchValue): Builder
    {
        return match ($field->searchType) {
            SearchTypes::$CONTAINS => $query->orWhere($field->fieldName, 'LIKE', '%' . $searchValue . '%'),
            SearchTypes::$EXACT => $query->orWhere($field->fieldName, '=', $searchValue),
            default => throw new Exception(),
        };
    }
}
