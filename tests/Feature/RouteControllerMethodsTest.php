<?php

use Tests\TestCase;
use Illuminate\Support\Facades\Route as RouteFacade;

/**
 * Test that all controller methods exist for registered routes.
 */
class RouteControllerMethodsTest extends TestCase
{
    public function testAllControllerMethodsExist()
    {
        $routes = RouteFacade::getRoutes();

        foreach ($routes as $route) {
            $action = $route->getAction();

            // Skip routes that don't have a controller
            if (!isset($action['controller'])) {
                continue;
            }

            $controllerAction = $action['controller'];
            list($controllerClass, $methodName) = explode('@', $controllerAction);

            $this->assertTrue(
                method_exists($controllerClass, $methodName),
                "Method {$methodName} does not exist in controller {$controllerClass} for route: {$route->uri()}"
            );
        }
    }
}
