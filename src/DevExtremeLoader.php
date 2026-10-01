<?php

declare(strict_types=1);

namespace DevExtreme\Data\Laravel;

use DevExtreme\Data\ArraySource;
use DevExtreme\Data\Contracts\DataSourceInterface;
use DevExtreme\Data\LoadOptions;
use DevExtreme\Data\LoadResult;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * The service behind the `DevExtreme` facade.
 */
final class DevExtremeLoader
{
    /**
     * @param \Closure(): Request $currentRequest resolves the request being handled, at call time
     */
    public function __construct(
        private readonly \Closure $currentRequest,
        private readonly bool $normalizeDates = true,
        private readonly ?int $maxTake = null,
    ) {
    }

    /**
     * Parses the DevExtreme parameters of a request (query string, form or JSON body).
     *
     * @throws BadRequestHttpException when a parameter is malformed
     */
    public function options(?Request $request = null): LoadOptions
    {
        try {
            $options = LoadOptions::fromArray(($request ?? ($this->currentRequest)())->all());
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        if ($this->maxTake !== null && !$options->isCountQuery && !$options->isSummaryQuery
            && ($options->take < 1 || $options->take > $this->maxTake)) {
            $options->take = $this->maxTake;
        }

        return $options;
    }

    /**
     * Answers the request for an Eloquent builder/relation/model class, query builder, collection, array or any data source.
     *
     * @param mixed $source see {@see source()}
     *
     * @throws BadRequestHttpException when the request is malformed (rendered by Laravel as HTTP 400)
     */
    public function load(mixed $source, ?Request $request = null): LoadResult
    {
        $options = $this->options($request);

        try {
            return $this->source($source)->load($options);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }
    }

    /**
     * Same as {@see load()}, wrapped in a JSON response.
     */
    public function response(mixed $source, ?Request $request = null): JsonResponse
    {
        return new JsonResponse($this->load($source, $request));
    }

    /**
     * @param EloquentBuilder<Model>|QueryBuilder|Relation<Model, Model, mixed>|Collection<array-key, mixed>|iterable<mixed>|DataSourceInterface|class-string<Model> $source
     */
    public function source(mixed $source): DataSourceInterface
    {
        return match (true) {
            $source instanceof DataSourceInterface => $source,
            $source instanceof EloquentBuilder,
            $source instanceof QueryBuilder,
            $source instanceof Relation => EloquentSource::for($source, normalizeDates: $this->normalizeDates),
            is_string($source) && is_subclass_of($source, Model::class) => EloquentSource::for($source, normalizeDates: $this->normalizeDates),
            $source instanceof Collection => new ArraySource($source->all()),
            is_iterable($source) => new ArraySource($source),
            default => throw new \LogicException('Unsupported data source: ' . get_debug_type($source)),
        };
    }
}
