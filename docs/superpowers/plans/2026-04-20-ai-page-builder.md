# AI Page Builder Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a standalone WordPress plugin that analyzes a design image or URL via AI Vision (Gemini/Claude/OpenAI) and auto-creates a draft Elementor page based on the detected layout.

**Architecture:** Admin menu page with 3 tabs (Analyse, Einstellungen, Kosten). Analysis goes via WP AJAX → PHP calls AI provider → parses JSON → builds Elementor page structure → creates WP draft post with `_elementor_data` meta. Widget mapping and cost tracking are isolated classes.

**Tech Stack:** PHP 8.0+, WordPress 6.0+, Elementor Free 3.0+, PHPUnit 9 + WP_Mock, Vanilla JS, WP AJAX/HTTP/Options API.

---

### Task 1: Plugin Scaffold & Test Infrastructure

**Files:**
- Create: `ai-page-builder/ai-page-builder.php`
- Create: `ai-page-builder/composer.json`
- Create: `ai-page-builder/phpunit.xml`
- Create: `ai-page-builder/tests/bootstrap.php`

- [ ] Create plugin directory structure
```bash
mkdir -p /path/to/wp-content/plugins/ai-page-builder/{includes,assets,templates,tests}
```
Replace `/path/to/wp-content/plugins` with your WordPress plugins path.

- [ ] Create `ai-page-builder/composer.json`
```json
{
  "require-dev": {
    "phpunit/phpunit": "^9.6",
    "10up/wp_mock": "^0.5"
  }
}
```

- [ ] Run `composer install` in `ai-page-builder/`

- [ ] Create `ai-page-builder/phpunit.xml`
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php" colors="true">
  <testsuites>
    <testsuite name="AI Page Builder">
      <directory>tests/</directory>
    </testsuite>
  </testsuites>
</phpunit>
```

- [ ] Create `ai-page-builder/tests/bootstrap.php`
```php
<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';
WP_Mock::bootstrap();
```

- [ ] Create `ai-page-builder/ai-page-builder.php`
```php
<?php
/**
 * Plugin Name: AI Page Builder
 * Description: Analyze a design image or URL and auto-create an Elementor page.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

defined('ABSPATH') || exit;

define('AIPB_VERSION',    '1.0.0');
define('AIPB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AIPB_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once AIPB_PLUGIN_DIR . 'includes/class-widget-map.php';
require_once AIPB_PLUGIN_DIR . 'includes/class-cost-tracker.php';
require_once AIPB_PLUGIN_DIR . 'includes/class-url-fetcher.php';
require_once AIPB_PLUGIN_DIR . 'includes/class-ai-analyzer.php';
require_once AIPB_PLUGIN_DIR . 'includes/class-elementor-builder.php';
require_once AIPB_PLUGIN_DIR . 'includes/class-admin-page.php';

register_activation_hook(__FILE__, 'aipb_activate');

function aipb_activate(): void {
    if (!did_action('elementor/loaded')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('AI Page Builder requires Elementor to be installed and activated.', 'Plugin Activation Error', ['back_link' => true]);
    }
}

add_action('plugins_loaded', function () {
    (new AIPB_Admin_Page())->init();
});
```

- [ ] Verify plugin appears in WP Admin → Plugins without errors

- [ ] Commit
```bash
git add ai-page-builder/
git commit -m "feat: scaffold AI Page Builder plugin"
```

---

### Task 2: Widget Map

**Files:**
- Create: `includes/class-widget-map.php`
- Create: `tests/test-widget-map.php`

- [ ] Write failing test `tests/test-widget-map.php`
```php
<?php
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/includes/class-widget-map.php';

class WidgetMapTest extends TestCase {
    private AIPB_Widget_Map $map;

    protected function setUp(): void {
        $this->map = new AIPB_Widget_Map();
    }

    public function test_heading_returns_correct_widget_type(): void {
        $result = $this->map->get('heading', 'Hello');
        $this->assertEquals('heading', $result['widgetType']);
        $this->assertEquals('Hello', $result['settings']['title']);
    }

    public function test_button_returns_text_in_settings(): void {
        $result = $this->map->get('button', 'Click me');
        $this->assertEquals('button', $result['widgetType']);
        $this->assertEquals('Click me', $result['settings']['text']);
    }

    public function test_text_editor_returns_content(): void {
        $result = $this->map->get('text-editor', 'Some text');
        $this->assertEquals('text-editor', $result['widgetType']);
        $this->assertEquals('Some text', $result['settings']['editor']);
    }

    public function test_unknown_type_falls_back_to_text_editor(): void {
        $result = $this->map->get('unknown', 'content');
        $this->assertEquals('text-editor', $result['widgetType']);
    }

    public function test_counter_extracts_number_from_content(): void {
        $result = $this->map->get('counter', '150 satisfied clients');
        $this->assertEquals(150, $result['settings']['ending_number']);
    }
}
```

- [ ] Run test — verify FAIL
```bash
cd ai-page-builder && ./vendor/bin/phpunit tests/test-widget-map.php -v
```
Expected: `Class "AIPB_Widget_Map" not found`

- [ ] Create `includes/class-widget-map.php`
```php
<?php
defined('ABSPATH') || exit;

class AIPB_Widget_Map {

    public function get(string $type, string $content): array {
        return match ($type) {
            'heading'     => ['widgetType' => 'heading',       'settings' => ['title' => $content ?: 'Heading', 'header_size' => 'h2']],
            'text-editor' => ['widgetType' => 'text-editor',   'settings' => ['editor' => $content ?: 'Text content.']],
            'image'       => ['widgetType' => 'image',         'settings' => ['image' => ['url' => '', 'id' => '']]],
            'button'      => ['widgetType' => 'button',        'settings' => ['text' => $content ?: 'Button', 'link' => ['url' => '#']]],
            'divider'     => ['widgetType' => 'divider',       'settings' => []],
            'spacer'      => ['widgetType' => 'spacer',        'settings' => ['space' => ['size' => 50, 'unit' => 'px']]],
            'icon-box'    => ['widgetType' => 'icon-box',      'settings' => ['title_text' => $content ?: 'Icon Box', 'description_text' => '']],
            'counter'     => $this->counter($content),
            'gallery'     => ['widgetType' => 'image-gallery', 'settings' => ['wp_gallery' => []]],
            'testimonial' => ['widgetType' => 'testimonial',   'settings' => ['testimonial_content' => $content ?: 'Testimonial.', 'testimonial_name' => 'Customer']],
            'tabs'        => ['widgetType' => 'tabs',          'settings' => ['tabs' => [['tab_title' => 'Tab 1', 'tab_content' => $content], ['tab_title' => 'Tab 2', 'tab_content' => '']]]],
            'accordion'   => ['widgetType' => 'accordion',     'settings' => ['tabs' => [['tab_title' => 'Item 1', 'tab_content' => $content], ['tab_title' => 'Item 2', 'tab_content' => '']]]],
            'video'       => ['widgetType' => 'video',         'settings' => ['video_type' => 'youtube', 'link' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']],
            'posts'       => ['widgetType' => 'posts',         'settings' => ['posts_per_page' => 3, 'columns' => '3']],
            'google-maps' => ['widgetType' => 'google-maps',   'settings' => ['address' => 'New York, USA', 'zoom' => ['size' => 10]]],
            default       => ['widgetType' => 'text-editor',   'settings' => ['editor' => $content ?: 'Content.']],
        };
    }

    private function counter(string $content): array {
        preg_match('/\d+/', $content, $m);
        return ['widgetType' => 'counter', 'settings' => ['starting_number' => 0, 'ending_number' => isset($m[0]) ? (int)$m[0] : 100, 'suffix' => '']];
    }
}
```

- [ ] Run test — verify PASS
```bash
./vendor/bin/phpunit tests/test-widget-map.php -v
```
Expected: 5 tests, 5 assertions, OK

- [ ] Commit
```bash
git add includes/class-widget-map.php tests/test-widget-map.php
git commit -m "feat: add widget map with 15 Elementor widget types"
```

---

### Task 3: URL Fetcher

**Files:**
- Create: `includes/class-url-fetcher.php`
- Create: `tests/test-url-fetcher.php`

- [ ] Write failing test `tests/test-url-fetcher.php`
```php
<?php
use WP_Mock\Tools\TestCase as WPTestCase;

require_once dirname(__DIR__) . '/includes/class-url-fetcher.php';

class UrlFetcherTest extends WPTestCase {

    public function test_clean_html_removes_script_tags(): void {
        $fetcher = new AIPB_Url_Fetcher();
        $result  = $fetcher->clean_html('<body><script>alert(1)</script><h1>Hello</h1></body>');
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('Hello', $result);
    }

    public function test_clean_html_removes_style_tags(): void {
        $fetcher = new AIPB_Url_Fetcher();
        $result  = $fetcher->clean_html('<body><style>.x{color:red}</style><p>Text</p></body>');
        $this->assertStringNotContainsString('<style>', $result);
        $this->assertStringContainsString('Text', $result);
    }

    public function test_fetch_returns_wp_error_on_http_failure(): void {
        WP_Mock::userFunction('wp_remote_get', [
            'return' => new \WP_Error('http_request_failed', 'Connection refused'),
        ]);
        WP_Mock::userFunction('is_wp_error', ['return' => true]);

        $fetcher = new AIPB_Url_Fetcher();
        $result  = $fetcher->fetch('https://example.com');
        $this->assertInstanceOf(\WP_Error::class, $result);
    }
}
```

- [ ] Run test — verify FAIL
```bash
./vendor/bin/phpunit tests/test-url-fetcher.php -v
```

- [ ] Create `includes/class-url-fetcher.php`
```php
<?php
defined('ABSPATH') || exit;

class AIPB_Url_Fetcher {

    public function fetch(string $url): string|\WP_Error {
        $response = wp_remote_get($url, ['timeout' => 15, 'sslverify' => false]);

        if (is_wp_error($response)) return $response;

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return new \WP_Error('fetch_failed', "HTTP {$code} from {$url}");
        }

        return $this->clean_html(wp_remote_retrieve_body($response));
    }

    public function clean_html(string $html): string {
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);
        $html = preg_replace('/<!--.*?-->/s', '', $html);
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $m)) {
            $html = $m[1];
        }
        return trim(preg_replace('/\s+/', ' ', $html));
    }
}
```

- [ ] Run test — verify PASS
```bash
./vendor/bin/phpunit tests/test-url-fetcher.php -v
```

- [ ] Commit
```bash
git add includes/class-url-fetcher.php tests/test-url-fetcher.php
git commit -m "feat: add URL fetcher with HTML cleaning"
```

---

### Task 4: Cost Tracker

**Files:**
- Create: `includes/class-cost-tracker.php`
- Create: `tests/test-cost-tracker.php`

- [ ] Write failing test `tests/test-cost-tracker.php`
```php
<?php
use WP_Mock\Tools\TestCase as WPTestCase;

require_once dirname(__DIR__) . '/includes/class-cost-tracker.php';

class CostTrackerTest extends WPTestCase {

    public function test_gemini_is_free(): void {
        $this->assertEquals(0.0, (new AIPB_Cost_Tracker())->calculate_cost('gemini', 1000000, 1000000));
    }

    public function test_claude_sonnet_cost(): void {
        // $3/1M input + $15/1M output
        $this->assertEquals(18.0, (new AIPB_Cost_Tracker())->calculate_cost('claude', 1000000, 1000000));
    }

    public function test_openai_gpt4o_cost(): void {
        // $2.50/1M input + $10/1M output
        $this->assertEquals(12.5, (new AIPB_Cost_Tracker())->calculate_cost('openai', 1000000, 1000000));
    }

    public function test_is_over_limit_false_when_limit_zero(): void {
        WP_Mock::userFunction('get_option', ['return' => []]);
        $this->assertFalse((new AIPB_Cost_Tracker())->is_over_limit(0));
    }

    public function test_record_calls_update_option(): void {
        WP_Mock::userFunction('get_option', ['args' => ['aipb_cost_log', []], 'return' => []]);
        WP_Mock::userFunction('current_time', ['return' => '2026-04-20 10:00:00']);
        WP_Mock::userFunction('update_option')->once();
        (new AIPB_Cost_Tracker())->record('gemini', 100, 50, 0.0);
        $this->assertTrue(true);
    }
}
```

- [ ] Run test — verify FAIL

- [ ] Create `includes/class-cost-tracker.php`
```php
<?php
defined('ABSPATH') || exit;

class AIPB_Cost_Tracker {

    private const PRICES = [
        'gemini' => ['input' => 0.0,   'output' => 0.0],
        'claude' => ['input' => 3.0,   'output' => 15.0],
        'openai' => ['input' => 2.50,  'output' => 10.0],
    ];

    public function calculate_cost(string $provider, int $input_tokens, int $output_tokens): float {
        $p = self::PRICES[$provider] ?? self::PRICES['gemini'];
        return round($input_tokens / 1_000_000 * $p['input'] + $output_tokens / 1_000_000 * $p['output'], 6);
    }

    public function record(string $provider, int $input_tokens, int $output_tokens, float $cost): void {
        $log   = get_option('aipb_cost_log', []);
        $log[] = ['date' => current_time('mysql'), 'provider' => $provider, 'input_tokens' => $input_tokens, 'output_tokens' => $output_tokens, 'cost' => $cost];
        update_option('aipb_cost_log', $log);
    }

    public function get_log(): array   { return get_option('aipb_cost_log', []); }
    public function get_total(): float { return array_sum(array_column($this->get_log(), 'cost')); }

    public function is_over_limit(float $limit): bool {
        if ($limit <= 0) return false;
        return $this->get_total() >= $limit;
    }
}
```

- [ ] Run test — verify PASS

- [ ] Commit
```bash
git add includes/class-cost-tracker.php tests/test-cost-tracker.php
git commit -m "feat: add cost tracker with per-provider pricing"
```

---

### Task 5: AI Analyzer

**Files:**
- Create: `includes/class-ai-analyzer.php`
- Create: `tests/test-ai-analyzer.php`

- [ ] Write failing test `tests/test-ai-analyzer.php`
```php
<?php
use WP_Mock\Tools\TestCase as WPTestCase;

require_once dirname(__DIR__) . '/includes/class-ai-analyzer.php';

class AiAnalyzerTest extends WPTestCase {

    private string $valid_json = '[{"section":1,"columns":2,"widgets":[{"column":1,"type":"heading","content":"Hi"}]}]';

    public function test_parse_response_returns_array_on_valid_json(): void {
        $result = (new AIPB_AI_Analyzer('gemini', 'key', 'gemini-2.0-flash'))->parse_response($this->valid_json);
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    public function test_parse_response_strips_markdown_code_fence(): void {
        $wrapped = "```json\n" . $this->valid_json . "\n```";
        $result  = (new AIPB_AI_Analyzer('gemini', 'key', 'gemini-2.0-flash'))->parse_response($wrapped);
        $this->assertIsArray($result);
    }

    public function test_parse_response_returns_wp_error_on_invalid_json(): void {
        $result = (new AIPB_AI_Analyzer('gemini', 'key', 'gemini-2.0-flash'))->parse_response('not json');
        $this->assertInstanceOf(\WP_Error::class, $result);
    }

    public function test_build_prompt_contains_required_widget_types(): void {
        $prompt = (new AIPB_AI_Analyzer('gemini', 'key', 'gemini-2.0-flash'))->build_prompt(false);
        $this->assertStringContainsString('heading', $prompt);
        $this->assertStringContainsString('button', $prompt);
        $this->assertStringContainsString('JSON', $prompt);
    }
}
```

- [ ] Run test — verify FAIL

- [ ] Create `includes/class-ai-analyzer.php`
```php
<?php
defined('ABSPATH') || exit;

class AIPB_AI_Analyzer {

    public function __construct(
        private string $provider,
        private string $api_key,
        private string $model
    ) {}

    public function analyze_image(string $base64, string $mime_type, bool $include_content): array|\WP_Error {
        $prompt = $this->build_prompt($include_content);
        return match ($this->provider) {
            'gemini' => $this->call_gemini_vision($base64, $mime_type, $prompt),
            'claude' => $this->call_claude_vision($base64, $mime_type, $prompt),
            'openai' => $this->call_openai_vision($base64, $mime_type, $prompt),
            default  => new \WP_Error('invalid_provider', 'Unknown provider: ' . $this->provider),
        };
    }

    public function analyze_html(string $html, bool $include_content): array|\WP_Error {
        $prompt = $this->build_prompt($include_content, true) . "\n\nHTML:\n" . substr($html, 0, 15000);
        return match ($this->provider) {
            'gemini' => $this->call_gemini_text($prompt),
            'claude' => $this->call_claude_text($prompt),
            'openai' => $this->call_openai_text($prompt),
            default  => new \WP_Error('invalid_provider', 'Unknown provider: ' . $this->provider),
        };
    }

    public function build_prompt(bool $include_content, bool $is_html = false): string {
        $source  = $is_html ? 'HTML source code' : 'webpage/design image';
        $content = $include_content ? 'Include detected text in "content" fields.' : 'Leave all "content" fields empty.';
        return <<<P
Analyze this {$source} and return ONLY a valid JSON array representing the layout as Elementor widgets. No explanation, only raw JSON.
{$content}
Supported widget types: heading, text-editor, image, button, divider, spacer, icon-box, counter, gallery, testimonial, tabs, accordion, video, posts, google-maps
Format: [{"section":1,"columns":2,"widgets":[{"column":1,"type":"heading","content":"Title"},{"column":2,"type":"image","content":""}]}]
P;
    }

    public function parse_response(string $raw): array|\WP_Error {
        $clean = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
        $clean = preg_replace('/\s*```$/', '', $clean);
        $data  = json_decode(trim($clean), true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            return new \WP_Error('parse_failed', 'AI returned invalid JSON: ' . substr($raw, 0, 200));
        }
        return $data;
    }

    // --- Gemini ---

    private function call_gemini_vision(string $base64, string $mime_type, string $prompt): array|\WP_Error {
        $body = json_encode(['contents' => [['parts' => [
            ['inline_data' => ['mime_type' => $mime_type, 'data' => $base64]],
            ['text' => $prompt],
        ]]]]);
        return $this->handle_gemini(wp_remote_post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->api_key}",
            ['body' => $body, 'headers' => ['Content-Type' => 'application/json'], 'timeout' => 60]
        ));
    }

    private function call_gemini_text(string $prompt): array|\WP_Error {
        $body = json_encode(['contents' => [['parts' => [['text' => $prompt]]]]]);
        return $this->handle_gemini(wp_remote_post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->api_key}",
            ['body' => $body, 'headers' => ['Content-Type' => 'application/json'], 'timeout' => 60]
        ));
    }

    private function handle_gemini(array|\WP_Error $response): array|\WP_Error {
        if (is_wp_error($response)) return $response;
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!$text) return new \WP_Error('gemini_error', 'Empty Gemini response');
        $parsed = $this->parse_response($text);
        if (is_wp_error($parsed)) return $parsed;
        return ['data' => $parsed, 'input_tokens' => $body['usageMetadata']['promptTokenCount'] ?? 0, 'output_tokens' => $body['usageMetadata']['candidatesTokenCount'] ?? 0];
    }

    // --- Claude ---

    private function call_claude_vision(string $base64, string $mime_type, string $prompt): array|\WP_Error {
        $body = json_encode(['model' => $this->model, 'max_tokens' => 4096, 'messages' => [['role' => 'user', 'content' => [
            ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mime_type, 'data' => $base64]],
            ['type' => 'text', 'text' => $prompt],
        ]]]]);
        return $this->handle_claude(wp_remote_post('https://api.anthropic.com/v1/messages', [
            'body' => $body, 'headers' => ['Content-Type' => 'application/json', 'x-api-key' => $this->api_key, 'anthropic-version' => '2023-06-01'], 'timeout' => 60,
        ]));
    }

    private function call_claude_text(string $prompt): array|\WP_Error {
        $body = json_encode(['model' => $this->model, 'max_tokens' => 4096, 'messages' => [['role' => 'user', 'content' => $prompt]]]);
        return $this->handle_claude(wp_remote_post('https://api.anthropic.com/v1/messages', [
            'body' => $body, 'headers' => ['Content-Type' => 'application/json', 'x-api-key' => $this->api_key, 'anthropic-version' => '2023-06-01'], 'timeout' => 60,
        ]));
    }

    private function handle_claude(array|\WP_Error $response): array|\WP_Error {
        if (is_wp_error($response)) return $response;
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $text = $body['content'][0]['text'] ?? null;
        if (!$text) return new \WP_Error('claude_error', 'Empty Claude response: ' . wp_remote_retrieve_body($response));
        $parsed = $this->parse_response($text);
        if (is_wp_error($parsed)) return $parsed;
        return ['data' => $parsed, 'input_tokens' => $body['usage']['input_tokens'] ?? 0, 'output_tokens' => $body['usage']['output_tokens'] ?? 0];
    }

    // --- OpenAI ---

    private function call_openai_vision(string $base64, string $mime_type, string $prompt): array|\WP_Error {
        $body = json_encode(['model' => $this->model, 'max_tokens' => 4096, 'messages' => [['role' => 'user', 'content' => [
            ['type' => 'image_url', 'image_url' => ['url' => "data:{$mime_type};base64,{$base64}"]],
            ['type' => 'text', 'text' => $prompt],
        ]]]]);
        return $this->handle_openai(wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'body' => $body, 'headers' => ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $this->api_key], 'timeout' => 60,
        ]));
    }

    private function call_openai_text(string $prompt): array|\WP_Error {
        $body = json_encode(['model' => $this->model, 'max_tokens' => 4096, 'messages' => [['role' => 'user', 'content' => $prompt]]]);
        return $this->handle_openai(wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'body' => $body, 'headers' => ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $this->api_key], 'timeout' => 60,
        ]));
    }

    private function handle_openai(array|\WP_Error $response): array|\WP_Error {
        if (is_wp_error($response)) return $response;
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $text = $body['choices'][0]['message']['content'] ?? null;
        if (!$text) return new \WP_Error('openai_error', 'Empty OpenAI response: ' . wp_remote_retrieve_body($response));
        $parsed = $this->parse_response($text);
        if (is_wp_error($parsed)) return $parsed;
        return ['data' => $parsed, 'input_tokens' => $body['usage']['prompt_tokens'] ?? 0, 'output_tokens' => $body['usage']['completion_tokens'] ?? 0];
    }
}
```

- [ ] Run test — verify PASS
```bash
./vendor/bin/phpunit tests/test-ai-analyzer.php -v
```

- [ ] Commit
```bash
git add includes/class-ai-analyzer.php tests/test-ai-analyzer.php
git commit -m "feat: add AI analyzer for Gemini, Claude, and OpenAI"
```

---

### Task 6: Elementor Builder

**Files:**
- Create: `includes/class-elementor-builder.php`
- Create: `tests/test-elementor-builder.php`

- [ ] Write failing test `tests/test-elementor-builder.php`
```php
<?php
use WP_Mock\Tools\TestCase as WPTestCase;

require_once dirname(__DIR__) . '/includes/class-widget-map.php';
require_once dirname(__DIR__) . '/includes/class-elementor-builder.php';

class ElementorBuilderTest extends WPTestCase {

    private AIPB_Elementor_Builder $builder;

    protected function setUp(): void {
        parent::setUp();
        $this->builder = new AIPB_Elementor_Builder(new AIPB_Widget_Map());
    }

    public function test_build_returns_array_with_one_section(): void {
        $result = $this->builder->build([['section' => 1, 'columns' => 1, 'widgets' => [['column' => 1, 'type' => 'heading', 'content' => 'Hi']]]]);
        $this->assertCount(1, $result);
        $this->assertEquals('section', $result[0]['elType']);
    }

    public function test_two_column_section_has_two_column_elements(): void {
        $result = $this->builder->build([[
            'section' => 1, 'columns' => 2,
            'widgets' => [['column' => 1, 'type' => 'heading', 'content' => 'L'], ['column' => 2, 'type' => 'image', 'content' => '']],
        ]]);
        $this->assertCount(2, $result[0]['elements']);
    }

    public function test_widget_has_non_empty_id(): void {
        $result = $this->builder->build([['section' => 1, 'columns' => 1, 'widgets' => [['column' => 1, 'type' => 'heading', 'content' => 'X']]]]);
        $this->assertNotEmpty($result[0]['elements'][0]['elements'][0]['id']);
    }

    public function test_widget_has_el_type_widget(): void {
        $result = $this->builder->build([['section' => 1, 'columns' => 1, 'widgets' => [['column' => 1, 'type' => 'button', 'content' => 'Go']]]]);
        $this->assertEquals('widget', $result[0]['elements'][0]['elements'][0]['elType']);
    }
}
```

- [ ] Run test — verify FAIL

- [ ] Create `includes/class-elementor-builder.php`
```php
<?php
defined('ABSPATH') || exit;

class AIPB_Elementor_Builder {

    public function __construct(private AIPB_Widget_Map $widget_map) {}

    public function build(array $sections): array {
        return array_map([$this, 'build_section'], $sections);
    }

    public function build_section(array $section): array {
        $columns     = max(1, (int)($section['columns'] ?? 1));
        $column_size = round(100 / $columns, 2);
        $widgets     = $section['widgets'] ?? [];

        $col_elements = [];
        for ($c = 1; $c <= $columns; $c++) {
            $col_widgets    = array_values(array_filter($widgets, fn($w) => (int)($w['column'] ?? 1) === $c));
            $col_elements[] = [
                'id'       => $this->uid(),
                'elType'   => 'column',
                'settings' => ['_column_size' => $column_size],
                'elements' => array_map([$this, 'build_widget'], $col_widgets),
            ];
        }

        return ['id' => $this->uid(), 'elType' => 'section', 'settings' => ['layout' => 'boxed'], 'elements' => $col_elements];
    }

    private function build_widget(array $widget): array {
        $map = $this->widget_map->get($widget['type'] ?? 'text-editor', $widget['content'] ?? '');
        return ['id' => $this->uid(), 'elType' => 'widget', 'widgetType' => $map['widgetType'], 'settings' => $map['settings'], 'elements' => []];
    }

    public function create_page(array $elementor_sections, string $title): int|\WP_Error {
        $post_id = wp_insert_post(['post_title' => sanitize_text_field($title), 'post_status' => 'draft', 'post_type' => 'page']);
        if (is_wp_error($post_id)) return $post_id;
        update_post_meta($post_id, '_elementor_data',          wp_slash(json_encode($elementor_sections)));
        update_post_meta($post_id, '_elementor_edit_mode',     'builder');
        update_post_meta($post_id, '_elementor_template_type', 'page');
        return $post_id;
    }

    private function uid(): string {
        return substr(md5(uniqid('', true)), 0, 7);
    }
}
```

- [ ] Run test — verify PASS

- [ ] Commit
```bash
git add includes/class-elementor-builder.php tests/test-elementor-builder.php
git commit -m "feat: add Elementor builder converting AI JSON to Elementor page structure"
```

---

### Task 7: Admin Page (Settings + Costs + AJAX handlers)

**Files:**
- Create: `includes/class-admin-page.php`
- Create: `assets/admin.css`

- [ ] Create `includes/class-admin-page.php`
```php
<?php
defined('ABSPATH') || exit;

class AIPB_Admin_Page {

    public function init(): void {
        add_action('admin_menu',                    [$this, 'register_menu']);
        add_action('admin_enqueue_scripts',         [$this, 'enqueue_assets']);
        add_action('wp_ajax_aipb_analyze',          [$this, 'ajax_analyze']);
        add_action('wp_ajax_aipb_create',           [$this, 'ajax_create']);
        add_action('wp_ajax_aipb_validate_key',     [$this, 'ajax_validate_key']);
        add_action('admin_post_aipb_save_settings', [$this, 'save_settings']);
    }

    public function register_menu(): void {
        add_menu_page('AI Page Builder', 'AI Page Builder', 'manage_options', 'ai-page-builder', [$this, 'render_page'], 'dashicons-layout', 30);
    }

    public function enqueue_assets(string $hook): void {
        if ($hook !== 'toplevel_page_ai-page-builder') return;
        wp_enqueue_style('aipb-admin',  AIPB_PLUGIN_URL . 'assets/admin.css', [], AIPB_VERSION);
        wp_enqueue_script('aipb-admin', AIPB_PLUGIN_URL . 'assets/admin.js',  [], AIPB_VERSION, true);
        wp_localize_script('aipb-admin', 'aipb', ['ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aipb_nonce')]);
    }

    public function render_page(): void {
        $tab = sanitize_key($_GET['tab'] ?? 'analyse');
        ?>
        <div class="wrap aipb-wrap">
            <h1>AI Page Builder</h1>
            <nav class="nav-tab-wrapper">
                <a href="?page=ai-page-builder&tab=analyse"  class="nav-tab <?= $tab === 'analyse'  ? 'nav-tab-active' : '' ?>">Analyse</a>
                <a href="?page=ai-page-builder&tab=settings" class="nav-tab <?= $tab === 'settings' ? 'nav-tab-active' : '' ?>">Einstellungen</a>
                <a href="?page=ai-page-builder&tab=costs"    class="nav-tab <?= $tab === 'costs'    ? 'nav-tab-active' : '' ?>">Kosten & Verlauf</a>
            </nav>
            <div class="aipb-tab-content">
                <?php match ($tab) {
                    'settings' => $this->render_settings_tab(),
                    'costs'    => $this->render_costs_tab(),
                    default    => $this->render_analyse_tab(),
                }; ?>
            </div>
        </div>
        <?php
    }

    private function render_analyse_tab(): void {
        include AIPB_PLUGIN_DIR . 'templates/preview.php';
    }

    private function render_settings_tab(): void {
        $s = $this->get_settings();
        if (!empty($_GET['saved'])) echo '<div class="notice notice-success"><p>Einstellungen gespeichert.</p></div>';
        ?>
        <form method="post" action="<?= esc_url(admin_url('admin-post.php')) ?>">
            <?php wp_nonce_field('aipb_save_settings'); ?>
            <input type="hidden" name="action" value="aipb_save_settings">
            <table class="form-table">
                <tr>
                    <th>AI Provider</th>
                    <td>
                        <select name="aipb_provider" id="aipb_provider">
                            <option value="gemini" <?= selected($s['provider'], 'gemini', false) ?>>Google Gemini (kostenlos)</option>
                            <option value="claude" <?= selected($s['provider'], 'claude', false) ?>>Anthropic Claude</option>
                            <option value="openai" <?= selected($s['provider'], 'openai', false) ?>>OpenAI GPT-4o</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>API Key</th>
                    <td>
                        <input type="password" name="aipb_api_key" value="<?= esc_attr($s['api_key']) ?>" class="regular-text" autocomplete="off">
                        <button type="button" id="aipb-validate-key" class="button">Key prüfen</button>
                        <span id="aipb-key-status"></span>
                    </td>
                </tr>
                <tr>
                    <th>Modell</th>
                    <td>
                        <select name="aipb_model">
                            <option value="gemini-2.0-flash"  <?= selected($s['model'], 'gemini-2.0-flash',  false) ?>>gemini-2.0-flash</option>
                            <option value="claude-sonnet-4-6" <?= selected($s['model'], 'claude-sonnet-4-6', false) ?>>claude-sonnet-4-6</option>
                            <option value="gpt-4o"            <?= selected($s['model'], 'gpt-4o',            false) ?>>gpt-4o</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>Kostenlimit (USD)</th>
                    <td>
                        <input type="number" name="aipb_cost_limit" value="<?= esc_attr($s['cost_limit']) ?>" step="0.01" min="0" class="small-text">
                        <p class="description">0 = kein Limit</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Einstellungen speichern'); ?>
        </form>
        <?php
    }

    private function render_costs_tab(): void {
        $tracker = new AIPB_Cost_Tracker();
        $log     = $tracker->get_log();
        $total   = $tracker->get_total();
        ?>
        <h2>Gesamtkosten: $<?= number_format($total, 4) ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>Datum</th><th>Provider</th><th>Input Tokens</th><th>Output Tokens</th><th>Kosten (USD)</th></tr></thead>
            <tbody>
                <?php if (empty($log)): ?>
                    <tr><td colspan="5">Noch keine Analysen durchgeführt.</td></tr>
                <?php else: foreach (array_reverse($log) as $e): ?>
                    <tr>
                        <td><?= esc_html($e['date']) ?></td>
                        <td><?= esc_html($e['provider']) ?></td>
                        <td><?= esc_html($e['input_tokens']) ?></td>
                        <td><?= esc_html($e['output_tokens']) ?></td>
                        <td>$<?= number_format($e['cost'], 6) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        <?php
    }

    public function save_settings(): void {
        check_admin_referer('aipb_save_settings');
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        update_option('aipb_settings', [
            'provider'   => sanitize_text_field($_POST['aipb_provider']   ?? 'gemini'),
            'api_key'    => sanitize_text_field($_POST['aipb_api_key']    ?? ''),
            'model'      => sanitize_text_field($_POST['aipb_model']      ?? 'gemini-2.0-flash'),
            'cost_limit' => (float)($_POST['aipb_cost_limit']             ?? 0),
        ]);
        wp_redirect(admin_url('admin.php?page=ai-page-builder&tab=settings&saved=1'));
        exit;
    }

    public function ajax_validate_key(): void {
        check_ajax_referer('aipb_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $analyzer = new AIPB_AI_Analyzer(
            sanitize_text_field($_POST['provider'] ?? ''),
            sanitize_text_field($_POST['api_key']  ?? ''),
            sanitize_text_field($_POST['model']    ?? '')
        );
        $result = $analyzer->analyze_html('Return [{"section":1,"columns":1,"widgets":[{"column":1,"type":"heading","content":"test"}]}]', false);
        is_wp_error($result) ? wp_send_json_error($result->get_error_message()) : wp_send_json_success('Key gültig ✓');
    }

    public function ajax_analyze(): void {
        check_ajax_referer('aipb_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $settings = $this->get_settings();
        $tracker  = new AIPB_Cost_Tracker();

        if ($tracker->is_over_limit($settings['cost_limit'])) {
            wp_send_json_error('Kostenlimit erreicht. Bitte in den Einstellungen erhöhen.');
        }

        $analyzer        = new AIPB_AI_Analyzer($settings['provider'], $settings['api_key'], $settings['model']);
        $include_content = !empty($_POST['include_content']);

        if (!empty($_FILES['image']['tmp_name'])) {
            $file      = $_FILES['image'];
            $mime      = mime_content_type($file['tmp_name']);
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                wp_send_json_error('Nur JPG, PNG und WebP werden unterstützt.');
            }
            if ($file['size'] > 10 * 1024 * 1024) {
                wp_send_json_error('Bild ist zu groß (max. 10 MB).');
            }
            $result = $analyzer->analyze_image(base64_encode(file_get_contents($file['tmp_name'])), $mime, $include_content);
        } elseif (!empty($_POST['url'])) {
            $html   = (new AIPB_Url_Fetcher())->fetch(sanitize_url($_POST['url']));
            $result = is_wp_error($html) ? $html : $analyzer->analyze_html($html, $include_content);
        } else {
            wp_send_json_error('Kein Bild und keine URL angegeben.');
        }

        if (is_wp_error($result)) wp_send_json_error($result->get_error_message());

        $cost = $tracker->calculate_cost($settings['provider'], $result['input_tokens'], $result['output_tokens']);
        $tracker->record($settings['provider'], $result['input_tokens'], $result['output_tokens'], $cost);

        wp_send_json_success(['sections' => $result['data']]);
    }

    public function ajax_create(): void {
        check_ajax_referer('aipb_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $sections = json_decode(stripslashes(sanitize_text_field($_POST['sections'] ?? '')), true);
        if (!is_array($sections)) wp_send_json_error('Ungültige Sektionsdaten.');

        $title   = sanitize_text_field($_POST['title'] ?? 'AI Generated Page');
        $builder = new AIPB_Elementor_Builder(new AIPB_Widget_Map());
        $post_id = $builder->create_page($builder->build($sections), $title);

        is_wp_error($post_id)
            ? wp_send_json_error($post_id->get_error_message())
            : wp_send_json_success(['post_id' => $post_id, 'edit_url' => get_edit_post_link($post_id, 'raw')]);
    }

    private function get_settings(): array {
        return wp_parse_args(get_option('aipb_settings', []), [
            'provider' => 'gemini', 'api_key' => '', 'model' => 'gemini-2.0-flash', 'cost_limit' => 0,
        ]);
    }
}
```

- [ ] Create `assets/admin.css`
```css
.aipb-wrap { max-width: 960px; }
.aipb-tab-content { padding: 20px 0; }
.aipb-input-row { display: flex; gap: 10px; align-items: center; margin-bottom: 12px; }
.aipb-or { font-weight: bold; color: #666; }
.aipb-preview { background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 16px; margin-top: 20px; }
.aipb-section { border: 1px solid #ccc; background: #fff; border-radius: 4px; padding: 12px; margin-bottom: 10px; }
.aipb-section-header { font-weight: bold; margin-bottom: 8px; }
.aipb-columns { display: flex; gap: 8px; }
.aipb-column { flex: 1; background: #f0f0f0; border-radius: 3px; padding: 8px; }
.aipb-widget { background: #e8f4fd; border: 1px solid #b3d9f5; border-radius: 3px; padding: 5px 8px; margin-bottom: 4px; font-size: 13px; }
.aipb-widget-type { font-weight: bold; color: #0073aa; }
.aipb-actions { margin-top: 16px; display: flex; gap: 10px; align-items: center; }
.aipb-result { margin-top: 12px; padding: 12px; background: #ecf7ed; border: 1px solid #46b450; border-radius: 4px; }
#aipb-key-status.success { color: #46b450; font-weight: bold; }
#aipb-key-status.error   { color: #dc3232; font-weight: bold; }
```

- [ ] Commit
```bash
git add includes/class-admin-page.php assets/admin.css
git commit -m "feat: add admin page with 3 tabs and AJAX handlers"
```

---

### Task 8: Analyse Tab Template & JavaScript

**Files:**
- Create: `templates/preview.php`
- Create: `assets/admin.js`

- [ ] Create `templates/preview.php`
```php
<?php defined('ABSPATH') || exit; ?>
<div class="aipb-analyse">
    <h2>Design analysieren</h2>
    <div class="aipb-input-row">
        <input type="text" id="aipb-url" placeholder="https://example.com" class="regular-text">
        <span class="aipb-or">ODER</span>
        <input type="file" id="aipb-image" accept="image/png,image/jpeg,image/webp">
    </div>
    <label><input type="checkbox" id="aipb-include-content"> Inhalt übernehmen (Text aus Design)</label><br><br>
    <button type="button" id="aipb-btn-analyse" class="button button-primary">Analysieren</button>
    <div id="aipb-loading" style="display:none;margin-top:10px;">
        <span class="spinner is-active" style="float:none;vertical-align:middle;"></span> Analyse läuft…
    </div>
    <div id="aipb-error" class="notice notice-error" style="display:none;"><p></p></div>
    <div id="aipb-preview" class="aipb-preview" style="display:none;">
        <h3>Erkannte Struktur</h3>
        <div id="aipb-sections"></div>
        <div class="aipb-actions">
            <input type="text" id="aipb-page-title" placeholder="Seitentitel" value="AI Generated Page" class="regular-text">
            <button type="button" id="aipb-btn-create" class="button button-primary">Elementor-Seite erstellen</button>
        </div>
    </div>
    <div id="aipb-result" class="aipb-result" style="display:none;">
        <strong>Seite erstellt!</strong> <a id="aipb-edit-link" href="#" target="_blank">Im Elementor-Editor öffnen →</a>
    </div>
</div>
```

- [ ] Create `assets/admin.js`
```js
/* global aipb */
(function () {
    var detectedSections = null;

    document.addEventListener('DOMContentLoaded', function () {
        var btnAnalyse  = document.getElementById('aipb-btn-analyse');
        var btnCreate   = document.getElementById('aipb-btn-create');
        var btnValidate = document.getElementById('aipb-validate-key');
        if (btnAnalyse)  btnAnalyse.addEventListener('click',  handleAnalyse);
        if (btnCreate)   btnCreate.addEventListener('click',   handleCreate);
        if (btnValidate) btnValidate.addEventListener('click', handleValidateKey);
    });

    function handleAnalyse() {
        var url     = (document.getElementById('aipb-url')     || {}).value || '';
        var fileEl  = document.getElementById('aipb-image');
        var image   = fileEl && fileEl.files[0];
        var include = document.getElementById('aipb-include-content').checked ? '1' : '0';

        if (!url.trim() && !image) { showError('Bitte URL eingeben oder Bild auswählen.'); return; }

        var fd = new FormData();
        fd.append('action', 'aipb_analyze');
        fd.append('nonce', aipb.nonce);
        fd.append('include_content', include);
        if (image) { fd.append('image', image); } else { fd.append('url', url.trim()); }

        setLoading(true);
        hideError();
        document.getElementById('aipb-preview').style.display = 'none';
        document.getElementById('aipb-result').style.display  = 'none';

        fetch(aipb.ajax_url, { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                setLoading(false);
                if (!res.success) { showError(res.data); return; }
                detectedSections = res.data.sections;
                renderPreview(detectedSections);
            })
            .catch(function (e) { setLoading(false); showError('Netzwerkfehler: ' + e.message); });
    }

    function renderPreview(sections) {
        var container = document.getElementById('aipb-sections');
        container.innerHTML = '';
        sections.forEach(function (section, idx) {
            var div = document.createElement('div');
            div.className = 'aipb-section';
            div.innerHTML = '<div class="aipb-section-header">Sektion ' + (idx + 1) + ' — ' + section.columns + ' Spalte(n)</div>';
            var cols = document.createElement('div');
            cols.className = 'aipb-columns';
            for (var c = 1; c <= section.columns; c++) {
                var col = document.createElement('div');
                col.className = 'aipb-column';
                col.innerHTML = '<small>Spalte ' + c + '</small>';
                (section.widgets || []).filter(function (w) { return w.column === c; }).forEach(function (w) {
                    var wd = document.createElement('div');
                    wd.className = 'aipb-widget';
                    wd.innerHTML = '<span class="aipb-widget-type">' + esc(w.type) + '</span>'
                        + (w.content ? ': ' + esc(String(w.content).substring(0, 60)) : '');
                    col.appendChild(wd);
                });
                cols.appendChild(col);
            }
            div.appendChild(cols);
            container.appendChild(div);
        });
        document.getElementById('aipb-preview').style.display = 'block';
    }

    function handleCreate() {
        if (!detectedSections) return;
        var title = document.getElementById('aipb-page-title').value.trim() || 'AI Generated Page';
        var fd = new FormData();
        fd.append('action',   'aipb_create');
        fd.append('nonce',    aipb.nonce);
        fd.append('sections', JSON.stringify(detectedSections));
        fd.append('title',    title);
        document.getElementById('aipb-btn-create').disabled = true;
        fetch(aipb.ajax_url, { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                document.getElementById('aipb-btn-create').disabled = false;
                if (!res.success) { showError(res.data); return; }
                document.getElementById('aipb-edit-link').href = res.data.edit_url;
                document.getElementById('aipb-result').style.display = 'block';
            })
            .catch(function (e) {
                document.getElementById('aipb-btn-create').disabled = false;
                showError('Netzwerkfehler: ' + e.message);
            });
    }

    function handleValidateKey() {
        var status   = document.getElementById('aipb-key-status');
        var provider = (document.getElementById('aipb_provider') || {}).value || '';
        var apiKey   = (document.querySelector('input[name="aipb_api_key"]') || {}).value || '';
        var model    = (document.querySelector('select[name="aipb_model"]')  || {}).value || '';
        status.textContent = 'Prüfe…'; status.className = '';
        var fd = new FormData();
        fd.append('action', 'aipb_validate_key'); fd.append('nonce', aipb.nonce);
        fd.append('provider', provider); fd.append('api_key', apiKey); fd.append('model', model);
        fetch(aipb.ajax_url, { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                status.textContent = res.success ? 'Key gültig ✓' : 'Ungültig: ' + res.data;
                status.className   = res.success ? 'success' : 'error';
            });
    }

    function setLoading(on) {
        document.getElementById('aipb-loading').style.display = on ? 'block' : 'none';
        var btn = document.getElementById('aipb-btn-analyse');
        if (btn) btn.disabled = on;
    }

    function showError(msg) {
        var el = document.getElementById('aipb-error');
        el.querySelector('p').textContent = msg;
        el.style.display = 'block';
    }

    function hideError() { document.getElementById('aipb-error').style.display = 'none'; }

    function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
}());
```

- [ ] Commit
```bash
git add templates/preview.php assets/admin.js
git commit -m "feat: add analyse tab UI and JavaScript"
```

---

### Task 9: End-to-End Manual Test

- [ ] Install plugin in WordPress with Elementor active (copy `ai-page-builder/` to `wp-content/plugins/`)

- [ ] Activate plugin — verify no errors, "AI Page Builder" appears in menu

- [ ] Go to Einstellungen tab
  - Select provider: Google Gemini
  - Enter API key (free at https://aistudio.google.com/app/apikey)
  - Click "Key prüfen" — expect "Key gültig ✓"
  - Save settings

- [ ] Go to Analyse tab
  - Upload the wireframe screenshot used in brainstorming
  - Click "Analysieren"
  - Expect: spinner → sections appear with column/widget breakdown

- [ ] Verify preview shows correct structure
  - Section 1: 2 columns (heading+text+button | image)
  - Section 2: 1 column (gallery)

- [ ] Enter title "Test Page", click "Elementor-Seite erstellen"
  - Expect: "Seite erstellt!" with editor link

- [ ] Open editor link — verify Elementor opens with sections and widgets

- [ ] Go to Kosten & Verlauf — verify entry with provider, tokens, cost appears

- [ ] Run full test suite
```bash
cd ai-page-builder && ./vendor/bin/phpunit --testdox
```
Expected: All tests pass

- [ ] Commit
```bash
git add .
git commit -m "feat: AI Page Builder Phase 1 complete"
```
