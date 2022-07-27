<?php

namespace App\Nova;

use Illuminate\Validation\Rule;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\FormData;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Http\Requests\NovaRequest;

/**
 * @template TModel of \App\Models\Project
 * @extends \App\Nova\Resource<TModel>
 */
class Project extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var class-string<TModel>
     */
    public static $model = \App\Models\Project::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        $productTypes = [
            'product' => 'Product',
            'service' => 'Service',
        ];

        return [
            ID::make(__('ID'), 'id')->sortable(),

            Select::make('Name')->options([
                'Nova' => 'Nova',
                'Spark' => 'Spark',
                'Forge' => 'Forge',
                'Envoyer' => 'Envoyer',
                'Vapor' => 'Vapor',
                'Secret' => 'Secret',
            ])->rules('required')->displayUsingLabels(),

            Code::make('Description')
                ->dependsOn('name', function (Code $field, NovaRequest $request, FormData $formData) {
                    if ($formData->name === 'Secret') {
                        $field->show()->value('## Laravel Labs');
                    }
                })
                ->language('text/x-markdown')
                ->hide()
                ->nullable(),

            Select::make('Type')->options([])->displayUsing(function ($value) use ($productTypes) {
                return $productTypes[$value] ?? null;
            })->dependsOn('name', function (Select $field, NovaRequest $request, FormData $formData) use ($productTypes) {
                if (in_array($formData->name, ['Nova', 'Spark'])) {
                    $field->options(collect($productTypes)->filter(function ($title, $type) {
                        return $type === 'product';
                    }))->default('product');
                } elseif (in_array($formData->name, ['Forge', 'Envoyer', 'Vapor'])) {
                    $field->options(collect($productTypes)->filter(function ($title, $type) {
                        return $type === 'service';
                    }))->default('service');
                } elseif (in_array($formData->name, ['Secret'])) {
                    $field->options($productTypes);
                }
            })->nullable()->rules(['nullable', Rule::in(array_keys($productTypes))]),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function lenses(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [];
    }
}
