<?php
// ---------------------------------------------------------
// Codebase Quicksearch
// Made by D.C. de Brabander
// ---------------------------------------------------------
// uses 'ag' command to search through codebase.
// Good to know; by default 'ag' respects .gitignore and
// therefor ignores vendor/node_modules folders.
//
// Usage:
// php codebase_quicksearch.php {string:KEYWORD} [optional: {bool:asHTML} {bool:orderPerBasepath} {bool:saveRaw}]
// 
// Example:
// php codebase_quicksearch.php sanoma true true
// ---------------------------------------------------------

// Read out given options (flags/bools)
$searchTerm = $argv[1] ?? null;         // What are we searching for?
$asHtmlTable = $argv[2] ?? false;       // Do you want an export as html table?
$orderPerBasepath = $argv[3] ?? false;  // Do you want this data to be order per base path?
$saveRaw = $arv[4] ?? false;            // Do you want save the raw results to a text file?

$filename = "search_{$searchTerm}_results_".time();
$htmlFilename = "$filename.html";

$command = "ag -ri --ignore-dir=logs --ignore-dir=cache '$searchTerm' *" . ($saveRaw ? "> $filename" : '');
$commandResults = [];

// What are we searching for?
if (empty($searchTerm)) {
    echo 'Enter something to search for please...';
    exit;
} else {
    echo "Searching for '$searchTerm' in " . getcwd() . PHP_EOL;
}

// Let's run our search.
echo "Running '$command' ..." . PHP_EOL;
exec($command, $commandResults);
if ($saveRaw) {
    echo "Done, raw results written into '$filename'" . PHP_EOL;
}

// Should give [0 => [path => '...', line => n, match => '...'], 1 => ...]
parseResults($commandResults);

// -------------------
// Real parsing / reading / etc. happens now.
// -------------------
// Parse our results for quicker/easier usage.
if ($asHtmlTable) {
    writeToHtml("<table>", $htmlFilename);
}

// Do we want to order results?
if ($orderPerBasepath) {
    orderCommandResultsPerPath($commandResults);
}

// The entire body as HTML
if ($asHtmlTable) {
    writeArrayToHtml($commandResults, $htmlFilename);
}

// Close table / file.
if ($asHtmlTable) {
    writeToHtml("</table>", $htmlFilename);
    echo "Done, see '$htmlFilename'" . PHP_EOL;
}
// --------------------

/**
 * Get array with path, line-number and matched string as key->value pairs
 * @param mixed $results
 * @return void
 */
function parseResults(array &$results)
{
    $results = array_filter($results);

    foreach ($results as $index => $result) {
        $partsOfResult = explode(':', $result);
    
        $pathOfResult = array_shift($partsOfResult);
        $lineNumber = array_shift($partsOfResult);
        $matchedString = implode(':', $partsOfResult);
    
        if (!$pathOfResult || !$lineNumber || !$matchedString) {
            continue;
        }

        $results[$index] = [
            'path' => $pathOfResult,
            'line' => $lineNumber,
            'match' => $matchedString
        ];

        // echo 'Found in "'. $pathOfResult .'" on line '. $lineNumber . PHP_EOL;
        // echo 'Matched on: ' . trim($matchedString) . PHP_EOL . PHP_EOL;
    }
}

/**
 * Parses array to html cells (td) and patches through to writeToHtml()
 * @param array $results
 * @param string $htmlFilename
 * @return void
 */
function writeArrayToHtml(array $results, string $htmlFilename)
{
    foreach ($results as $result) {
        $html = ['<tr>'];
        foreach ($result as &$cell) {
            if (is_string($cell)) {
                $cell = htmlspecialchars($cell);
                $html[] = "<td>{$cell}</td>";
            }
        }
        $html[] = '</tr>' . PHP_EOL;
        
        // Write table row.
        writeToHtml(implode('', $html), $htmlFilename);
    }
}

/**
 * Write to HTML file.
 * Should make it easier to paste it into Jira/Confluence or Google Sheets.
 * @param mixed $path
 * @param mixed $line
 * @param mixed $match
 * @return void
 */
function writeToHtml(string $html, string $file)
{
    if (empty($html)) {
        return;
    }
  
    $htmlFileHandle = fopen($file, "a") or die("Unable to open file!");
    fwrite($htmlFileHandle, $html);
    fclose($htmlFileHandle);
}

function orderCommandResultsPerPath(&$results)
{
    return $results;
}
