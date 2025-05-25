<?php

namespace App\Core;

use Exception;

class View
{
    public static function render(string $viewName, array $data = [], string $layout = null): void
    {
        // --- BEGIN VIEW DEBUGGING ---
        echo "<div style='background-color: #ffc; padding: 10px; border: 1px solid #ccc; margin-bottom:10px;'>";
        echo "DEBUG (View::render): Called for view '{$viewName}' with layout '{$layout}'.<br>";
        // --- END VIEW DEBUGGING ---

        $viewFile = __DIR__ . '/../../templates/' . str_replace('.', '/', $viewName) . '.php';

        // --- MORE VIEW DEBUGGING ---
        echo "DEBUG (View::render): Expecting view file at: {$viewFile}<br>";
        echo "DEBUG (View::render): View file exists? " . (file_exists($viewFile) ? 'Yes' : 'No') . "<br>";
        // --- END VIEW DEBUGGING ---

        if (!file_exists($viewFile)) {
            throw new Exception("View file not found: {$viewFile}");
        }

        extract($data);

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout) { // Check if a layout name was provided
            $layoutFile = __DIR__ . '/../../templates/layouts/' . $layout . '.php';

            // --- LAYOUT DEBUGGING ---
            echo "DEBUG (View::render): Layout specified. Expecting layout file at: {$layoutFile}<br>";
            echo "DEBUG (View::render): Layout file exists? " . (file_exists($layoutFile) ? 'Yes' : 'No') . "<br>";
            // --- END LAYOUT DEBUGGING ---

            if (!file_exists($layoutFile)) {
                // Close the initial debug div if layout fails early
                echo "</div>";
                throw new Exception("Layout file not found: {$layoutFile}");
            }
            // If we reach here, initial debug div will be closed by layout itself if layout is rendered.
            // If layout isn't rendered but content is, we need to close it.
            echo "</div>"; // Close the debug div BEFORE requiring layout or echoing content without layout.
            require $layoutFile; // $content is available to the layout
        } else {
            echo "DEBUG (View::render): No layout specified. Rendering view content directly.<br>";
            echo "</div>"; // Close the debug div
            echo $content;
        }
    }
}