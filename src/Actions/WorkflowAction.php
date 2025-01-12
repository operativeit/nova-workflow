<?php

namespace Orlyapps\NovaWorkflow\Actions;

use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class WorkflowAction extends Action
{
    public $name = 'Status Change';

    /**
     * Get the URI key for the action.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'workflow-status-change';
    }

    /**
     * Indicates if this action is only available on the resource detail view.
     *
     * @var bool
     */
    public $showOnDetail = false;

    /**
     * Indicates if this action is available on the resource index view.
     *
     * @var bool
     */
    public $showOnIndex = false;

    public $transition = null;

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        $refererUrl = request()->headers->get('referer');
        $refererParams = parse_url($refererUrl, PHP_URL_QUERY);
        parse_str($refererParams, $params);
        $this->transition = data_get($params, 'transition');

        foreach ($models as $model) {
            try {
                $workflow = \Workflow::get($model, \Str::lower(class_basename($model)));
                $workflow->apply($model, $this->transition ?? $model->getTransitionForAction($this));
                $model->save();
            } catch (\Throwable $th) {
                throw $th;
            }
        }
    }
}
