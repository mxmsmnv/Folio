<?php
$module = (string)file_get_contents(dirname(__DIR__) . '/Folio.module.php');
$trait = (string)file_get_contents(dirname(__DIR__) . '/src/McpProviderTrait.php');
$checks = [str_contains($module, '"mcpProvider" => true'), str_contains($trait, "'folio_status'"), str_contains($trait, "'private_records_exposed' => false"), str_contains($trait, "'write_tools' => false"), str_contains($trait, "'additionalProperties' => false")];
if(in_array(false, $checks, true)) { fwrite(STDERR, "Folio MCP provider contract failed.\n"); exit(1); }
echo "Folio MCP provider contract passed.\n";
