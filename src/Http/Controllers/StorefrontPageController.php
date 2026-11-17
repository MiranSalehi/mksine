<?php

declare(strict_types=1);

namespace Miran\Mksine\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Miran\Mksine\Livewire\Frontend\FrontendResolver;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders storefront pages as plain Blade, without the Livewire runtime.
 *
 * The page classes (PostShow, PageShow, …) are still Livewire components so themes and plugins
 * that extend them keep working, but here they are used as ordinary objects: public
 * properties are filled from the route, mount() and render() are called, and the returned view
 * is placed in the theme layout. A page therefore ships no livewire.js unless it contains a
 * real Livewire island (comments, cart), pagination links are plain `?page=` URLs, and the
 * page is not rendered twice through a wrapper component.
 *
 * A theme that overrides a page with its own Livewire component, or `mksine.frontend.livewire_pages`
 * set to true, keeps the previous full-page Livewire rendering ({@see FrontendResolver}).
 */
final class StorefrontPageController
{
    public function __invoke(Request $request): Response
    {
        [$pageKey, $params] = FrontendResolver::pageFromRoute($request->route());

        if ((bool) config('mksine.frontend.livewire_pages', false) || FrontendResolver::themeOverride($pageKey) !== null) {
            return $this->renderWithLivewire();
        }

        [$componentClass, $params] = FrontendResolver::target($pageKey, $params);

        $content = $this->renderComponent($componentClass, $params);

        return response(view(theme_layout(), ['slot' => new HtmlString($content)])->render());
    }

    /**
     * @param  class-string  $componentClass
     * @param  array<string, mixed>  $params
     */
    private function renderComponent(string $componentClass, array $params): string
    {
        /** @var Component $component */
        $component = app($componentClass);

        foreach ($params as $name => $value) {
            $this->assignPublicProperty($component, (string) $name, $value);
        }

        $this->assignPublicProperty($component, 'skipLayout', true);

        if (method_exists($component, 'mount')) {
            app()->call([$component, 'mount'], $this->mountArguments($component, $params));
        }

        $view = app()->call([$component, 'render']);

        if (! $view instanceof View) {
            return (string) $view;
        }

        // Livewire exposes a component's public properties to its view; keep that contract.
        $missing = array_diff_key($this->publicProperties($component), $view->getData());

        return $view->with($missing)->render();
    }

    private function renderWithLivewire(): Response
    {
        $resolver = app('livewire')->new(FrontendResolver::class);

        return app()->call([$resolver, '__invoke']);
    }

    private function assignPublicProperty(object $component, string $name, mixed $value): void
    {
        if (! property_exists($component, $name)) {
            return;
        }

        $property = new ReflectionProperty($component, $name);

        if (! $property->isPublic() || $property->isStatic() || $property->isReadOnly()) {
            return;
        }

        $type = $property->getType();

        if ($type instanceof ReflectionNamedType && $type->isBuiltin() && $value !== null) {
            $value = match ($type->getName()) {
                'int' => is_numeric($value) ? (int) $value : $value,
                'string' => is_scalar($value) ? (string) $value : $value,
                'bool' => (bool) $value,
                default => $value,
            };
        }

        $property->setValue($component, $value);
    }

    /**
     * Route parameters that mount() declares (Livewire passes only those, too).
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function mountArguments(object $component, array $params): array
    {
        $names = array_map(
            static fn (\ReflectionParameter $parameter): string => $parameter->getName(),
            (new ReflectionMethod($component, 'mount'))->getParameters(),
        );

        return array_intersect_key($params, array_flip($names));
    }

    /**
     * @return array<string, mixed>
     */
    private function publicProperties(object $component): array
    {
        $properties = [];

        foreach ((new \ReflectionObject($component))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic() || ! $property->isInitialized($component)) {
                continue;
            }

            $properties[$property->getName()] = $property->getValue($component);
        }

        return $properties;
    }
}
