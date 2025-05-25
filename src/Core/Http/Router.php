<?php

namespace App\Core\Http;

use Exception; // Make sure to use the global Exception

class Router
{
    private array $routes = [];

    public function addRoute(string $method, string $path, $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler
        ];
    }

    // Convenience method for GET requests
    public function get(string $path, $controllerOrClosure, string $method = null): void
    {
        if ($method === null && is_callable($controllerOrClosure)) { // It's a closure
            $this->addRoute('GET', $path, $controllerOrClosure);
        } elseif (is_string($controllerOrClosure) && class_exists($controllerOrClosure) && $method !== null) {
            // This case is for when it's like $router->get('/path', Controller::class, 'method');
            $this->addRoute('GET', $path, [$controllerOrClosure, $method]);
        } elseif (is_array($controllerOrClosure) && count($controllerOrClosure) === 2) {
            // This case is for when it's an array like $router->get('/path', [Controller::class, 'methodName']);
            $this->addRoute('GET', $path, $controllerOrClosure);
        } else {
            throw new Exception("Invalid GET route definition for path: {$path}");
        }
    }

    // Convenience method for POST requests
    public function post(string $path, array $controllerAction): void
    {
        // Expects $controllerAction to be [Controller::class, 'methodName']
        if (count($controllerAction) === 2 && is_string($controllerAction[0]) && class_exists($controllerAction[0]) && is_string($controllerAction[1])) {
            $this->addRoute('POST', $path, $controllerAction);
        } else {
            throw new Exception("Invalid POST route definition for path: {$path}");
        }
    }

    public function dispatch(string $requestMethod, string $requestUri): void
    {
        // --- BEGIN DEBUGGING LINES ---
       // echo "DEBUG: Router attempting to dispatch URI: '{$requestUri}' with Method: '{$requestMethod}'<br>";
       // echo "DEBUG: Registered Routes:<pre>";
      //  print_r($this->routes);
        //echo "</pre><hr>";
        // --- END DEBUGGING LINES ---

        foreach ($this->routes as $route) {
            // Simple direct path matching (no dynamic parameters like /users/{id} yet)
            if ($route['method'] === strtoupper($requestMethod) && $route['path'] === $requestUri) {
                $handler = $route['handler'];

                if (is_callable($handler) && !is_array($handler)) { // Is it a Closure and not an array?
                    call_user_func($handler);
                    return;
                }

                // Is it a [Controller::class, 'methodName'] array?
                if (is_array($handler) && count($handler) === 2) {
                    [$controllerClass, $methodName] = $handler;

                    if (class_exists($controllerClass)) {
                        $controllerInstance = new $controllerClass(); // Basic instantiation
                        if (method_exists($controllerInstance, $methodName)) {
                            // Call the method directly on the instance
                            $controllerInstance->$methodName(); // <--- THIS IS THE FIX
                            return;
                        } else {
                            throw new Exception("Method {$methodName} not found in controller {$controllerClass}");
                        }
                    } else {
                        throw new Exception("Controller class {$controllerClass} not found");
                    }
                }
                // If handler is not callable and not a valid controller array
                throw new Exception("Invalid handler for route {$requestMethod} {$requestUri}");
            }
        }
        // No route matched
        http_response_code(404);
        throw new Exception("No route found for {$requestMethod} {$requestUri}");
    }
}