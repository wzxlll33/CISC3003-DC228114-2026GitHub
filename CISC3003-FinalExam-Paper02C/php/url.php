<?php
function app_url(string $page, array $query = []): string
{
    $forwardedProto = strtolower((string) ($_SERVER["HTTP_X_FORWARDED_PROTO"] ?? ""));
    $isHttps = $forwardedProto === "https"
        || (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off")
        || (($_SERVER["SERVER_PORT"] ?? "") === "443");

    $scheme = $isHttps ? "https" : "http";
    $host = $_SERVER["HTTP_HOST"] ?? "localhost";
    $basePath = rtrim(str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"] ?? "")), "/");
    $path = ($basePath === "" ? "" : $basePath) . "/" . ltrim($page, "/");
    $url = $scheme . "://" . $host . $path;

    if ($query) {
        $url .= "?" . http_build_query($query);
    }

    return $url;
}
?>
