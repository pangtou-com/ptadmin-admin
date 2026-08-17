<?php

declare(strict_types=1);

namespace PTAdmin\Admin\Notifications;

use Illuminate\Support\Facades\DB;
use PTAdmin\Admin\Models\NotificationScene;
use PTAdmin\Admin\Models\NotificationTemplate;

final class NotificationSceneRegistry
{
    private const SCHEMA_VERSION = 1;

    /** @var NotificationTemplateRenderer */
    private $renderer;

    public function __construct(NotificationTemplateRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    public function syncAddon(string $addonCode, array $definition): void
    {
        $addonCode = $this->normalizeCode($addonCode, '插件编码', 100);
        $scenes = $this->normalizeDefinition($definition);

        DB::transaction(function () use ($addonCode, $scenes): void {
            $syncedSceneIds = [];

            foreach ($scenes as $sceneDefinition) {
                $scene = NotificationScene::query()->where('code', $sceneDefinition['code'])->first();
                if ($scene instanceof NotificationScene
                    && ('addon' !== $scene->source_type || $addonCode !== $scene->source_code)) {
                    throw new \InvalidArgumentException(
                        '通知场景 ['.$sceneDefinition['code'].'] 已由 ['.$scene->source_type.':'.$scene->source_code.'] 注册'
                    );
                }

                if (!$scene instanceof NotificationScene) {
                    $scene = new NotificationScene();
                    $scene->source_type = 'addon';
                    $scene->source_code = $addonCode;
                    $scene->code = $sceneDefinition['code'];
                }

                $scene->title = $sceneDefinition['title'];
                $scene->description = $sceneDefinition['description'];
                $scene->group_code = $sceneDefinition['group_code'];
                $scene->group_title = $sceneDefinition['group_title'];
                $scene->purpose = $sceneDefinition['purpose'];
                $scene->variables = $sceneDefinition['variables'];
                $scene->default_channels = $sceneDefinition['default_channels'];
                $scene->enabled = 1;
                $scene->save();

                $syncedSceneIds[] = (int) $scene->id;
                $this->syncTemplates($scene, $sceneDefinition['templates']);
            }

            $staleScenes = NotificationScene::query()
                ->where('source_type', 'addon')
                ->where('source_code', $addonCode)
                ->when([] !== $syncedSceneIds, static function ($query) use ($syncedSceneIds): void {
                    $query->whereNotIn('id', $syncedSceneIds);
                })
                ->get();

            foreach ($staleScenes as $scene) {
                $scene->enabled = 0;
                $scene->save();
                NotificationTemplate::query()->where('scene_id', (int) $scene->id)->update([
                    'enabled' => 0,
                    'updated_at' => time(),
                ]);
            }
        });
    }

    public function disableAddon(string $addonCode): void
    {
        $addonCode = $this->normalizeCode($addonCode, '插件编码', 100);

        DB::transaction(function () use ($addonCode): void {
            $sceneIds = NotificationScene::query()
                ->where('source_type', 'addon')
                ->where('source_code', $addonCode)
                ->pluck('id')
                ->all();

            if ([] === $sceneIds) {
                return;
            }

            NotificationScene::query()->whereIn('id', $sceneIds)->update([
                'enabled' => 0,
                'updated_at' => time(),
            ]);
            NotificationTemplate::query()->whereIn('scene_id', $sceneIds)->update([
                'enabled' => 0,
                'updated_at' => time(),
            ]);
        });
    }

    public function normalizeTemplateConfig(NotificationScene $scene, string $channel, string $mode, array $config): array
    {
        $channel = NotificationChannel::normalize($channel);
        if (!in_array($mode, NotificationTemplateMode::all(), true)) {
            throw new \InvalidArgumentException('通知场景 ['.$scene->code.'] 的渠道 ['.$channel.'] 模板模式无效');
        }

        $variableNames = array_fill_keys(array_column((array) $scene->variables, 'name'), true);

        return $this->normalizeTemplateConfigDefinition((string) $scene->code, $channel, $mode, $config, $variableNames);
    }

    private function normalizeTemplateConfigDefinition(
        string $scene,
        string $channel,
        string $mode,
        array $config,
        array $variableNames
    ): array
    {
        return NotificationTemplateMode::CONTENT === $mode
            ? $this->normalizeContentConfig($scene, $channel, $config, $variableNames)
            : $this->normalizeReferenceConfig($scene, $channel, $config, $variableNames);
    }

    private function syncTemplates(NotificationScene $scene, array $definitions): void
    {
        $existing = NotificationTemplate::query()
            ->where('scene_id', (int) $scene->id)
            ->get()
            ->keyBy(function (NotificationTemplate $template): string {
                return $template->channel.'|'.$template->locale;
            });
        $syncedKeys = [];

        foreach ($definitions as $definition) {
            $key = $definition['channel'].'|'.$definition['locale'];
            $syncedKeys[$key] = true;
            $template = $existing->get($key);

            if (!$template instanceof NotificationTemplate) {
                $template = new NotificationTemplate();
                $template->scene_id = (int) $scene->id;
                $template->channel = $definition['channel'];
                $template->locale = $definition['locale'];
                $template->customized = 0;
            } elseif ($template->mode !== $definition['mode']) {
                throw new \InvalidArgumentException(
                    '通知场景 ['.$scene->code.'] 的渠道 ['.$definition['channel'].'] 模板模式不能原地修改'
                );
            }

            $template->mode = $definition['mode'];
            if (0 === (int) $template->customized) {
                $template->config = $definition['config'];
            }
            $template->enabled = 1;
            $template->save();
        }

        foreach ($existing as $key => $template) {
            if (isset($syncedKeys[$key])) {
                continue;
            }

            $template->enabled = 0;
            $template->save();
        }
    }

    private function normalizeDefinition(array $definition): array
    {
        if (self::SCHEMA_VERSION !== ($definition['schema_version'] ?? null)) {
            throw new \InvalidArgumentException('通知配置 schema_version 仅支持版本 1');
        }
        if (!isset($definition['scenes']) || !is_array($definition['scenes'])) {
            throw new \InvalidArgumentException('通知配置 scenes 必须是数组');
        }

        $groups = $this->normalizeGroups($definition['groups'] ?? []);
        $scenes = [];
        foreach (array_values($definition['scenes']) as $index => $scene) {
            if (!is_array($scene)) {
                throw new \InvalidArgumentException('通知配置 scenes.'.$index.' 必须是数组');
            }

            $normalized = $this->normalizeScene($scene, $index, $groups);
            if (isset($scenes[$normalized['code']])) {
                throw new \InvalidArgumentException('通知场景编码 ['.$normalized['code'].'] 重复');
            }
            $scenes[$normalized['code']] = $normalized;
        }

        return array_values($scenes);
    }

    private function normalizeScene(array $scene, int $index, array $groups): array
    {
        $path = 'scenes.'.$index;
        $code = $this->normalizeCode($scene['code'] ?? null, $path.'.code', 100);
        $title = $this->requiredString($scene['title'] ?? null, $path.'.title', 255);
        $groupCode = $this->normalizeCode(
            $scene['group'] ?? NotificationSceneGroup::GENERAL,
            $path.'.group',
            50
        );
        if (!isset($groups[$groupCode])) {
            throw new \InvalidArgumentException('通知场景 ['.$code.'] 引用了未注册分组 ['.$groupCode.']');
        }
        $purpose = (string) ($scene['purpose'] ?? NotificationPurpose::TRANSACTIONAL);
        if (!in_array($purpose, NotificationPurpose::all(), true)) {
            throw new \InvalidArgumentException('通知场景 ['.$code.'] 的 purpose 无效');
        }

        $variables = $this->normalizeVariables($code, $scene['variables'] ?? []);
        $variableNames = array_fill_keys(array_column($variables, 'name'), true);
        $templates = $this->normalizeTemplates($code, $scene['templates'] ?? [], $variableNames);
        $defaultChannels = $this->normalizeChannels($scene['default_channels'] ?? [], '通知场景 ['.$code.']');
        $templateChannels = array_fill_keys(array_column($templates, 'channel'), true);
        foreach ($defaultChannels as $channel) {
            if (!isset($templateChannels[$channel])) {
                throw new \InvalidArgumentException('通知场景 ['.$code.'] 的默认渠道 ['.$channel.'] 未配置模板');
            }
        }

        $description = $scene['description'] ?? null;
        if (null !== $description && !is_string($description)) {
            throw new \InvalidArgumentException('通知场景 ['.$code.'] 的 description 必须是字符串');
        }

        return [
            'code' => $code,
            'title' => $title,
            'description' => null === $description ? null : mb_substr($description, 0, 500),
            'group_code' => $groupCode,
            'group_title' => $groups[$groupCode],
            'purpose' => $purpose,
            'variables' => $variables,
            'default_channels' => $defaultChannels,
            'templates' => $templates,
        ];
    }

    private function normalizeGroups($definitions): array
    {
        if (!is_array($definitions)) {
            throw new \InvalidArgumentException('通知配置 groups 必须是数组');
        }

        $groups = NotificationSceneGroup::titles();
        foreach (array_values($definitions) as $index => $definition) {
            if (!is_array($definition)) {
                throw new \InvalidArgumentException('通知配置 groups.'.$index.' 必须是数组');
            }

            $code = $this->normalizeCode($definition['code'] ?? null, 'groups.'.$index.'.code', 50);
            $title = $this->requiredString($definition['title'] ?? null, 'groups.'.$index.'.title', 100);
            $builtinTitle = NotificationSceneGroup::title($code);
            if (null !== $builtinTitle && $builtinTitle !== $title) {
                throw new \InvalidArgumentException('内置通知分组 ['.$code.'] 名称必须为 ['.$builtinTitle.']');
            }
            if (isset($groups[$code]) && null === $builtinTitle) {
                throw new \InvalidArgumentException('通知分组编码 ['.$code.'] 重复');
            }

            $groups[$code] = $title;
        }

        return $groups;
    }

    private function normalizeVariables(string $scene, $definitions): array
    {
        if (!is_array($definitions)) {
            throw new \InvalidArgumentException('通知场景 ['.$scene.'] 的 variables 必须是数组');
        }

        $variables = [];
        foreach (array_values($definitions) as $index => $definition) {
            if (!is_array($definition)) {
                throw new \InvalidArgumentException('通知场景 ['.$scene.'] 的 variables.'.$index.' 必须是数组');
            }

            $name = $this->normalizeCode($definition['name'] ?? null, '通知变量名', 100, false);
            if (isset($variables[$name])) {
                throw new \InvalidArgumentException('通知场景 ['.$scene.'] 的变量 ['.$name.'] 重复');
            }
            $type = (string) ($definition['type'] ?? '');
            if (!in_array($type, NotificationVariableType::all(), true)) {
                throw new \InvalidArgumentException('通知场景 ['.$scene.'] 的变量 ['.$name.'] 类型无效');
            }
            if (isset($definition['required']) && !is_bool($definition['required'])) {
                throw new \InvalidArgumentException('通知场景 ['.$scene.'] 的变量 ['.$name.'] required 必须是布尔值');
            }
            if (isset($definition['secret']) && !is_bool($definition['secret'])) {
                throw new \InvalidArgumentException('通知场景 ['.$scene.'] 的变量 ['.$name.'] secret 必须是布尔值');
            }
            if (isset($definition['rules'])) {
                if (!is_array($definition['rules']) || array_filter($definition['rules'], static function ($rule): bool {
                    return !is_string($rule);
                })) {
                    throw new \InvalidArgumentException('通知场景 ['.$scene.'] 的变量 ['.$name.'] rules 必须是字符串数组');
                }
            }
            if (true === ($definition['secret'] ?? false) && array_key_exists('mask', $definition)) {
                throw new \InvalidArgumentException('通知场景 ['.$scene.'] 的变量 ['.$name.'] 不能同时配置 secret 和 mask');
            }

            $definition['name'] = $name;
            $definition['type'] = $type;
            $definition['label'] = isset($definition['label']) ? (string) $definition['label'] : $name;
            $definition['required'] = true === ($definition['required'] ?? false);
            $definition['rules'] = array_values($definition['rules'] ?? []);
            $variables[$name] = $definition;
        }

        return array_values($variables);
    }

    private function normalizeTemplates(string $scene, $definitions, array $variableNames): array
    {
        if (!is_array($definitions) || [] === $definitions) {
            throw new \InvalidArgumentException('通知场景 ['.$scene.'] 必须配置 templates');
        }

        $templates = [];
        foreach (array_values($definitions) as $index => $definition) {
            if (!is_array($definition)) {
                throw new \InvalidArgumentException('通知场景 ['.$scene.'] 的 templates.'.$index.' 必须是数组');
            }

            $channel = NotificationChannel::normalize((string) ($definition['channel'] ?? ''));
            $locale = $this->normalizeLocale((string) ($definition['locale'] ?? 'zh-CN'));
            $mode = (string) ($definition['mode'] ?? '');
            if (!in_array($mode, NotificationTemplateMode::all(), true)) {
                throw new \InvalidArgumentException('通知场景 ['.$scene.'] 的渠道 ['.$channel.'] 模板模式无效');
            }

            $key = $channel.'|'.$locale;
            if (isset($templates[$key])) {
                throw new \InvalidArgumentException('通知场景 ['.$scene.'] 的渠道 ['.$channel.'] 与语言 ['.$locale.'] 模板重复');
            }

            $config = $definition;
            unset($config['channel'], $config['locale'], $config['mode']);
            $config = $this->normalizeTemplateConfigDefinition($scene, $channel, $mode, $config, $variableNames);

            $templates[$key] = [
                'channel' => $channel,
                'locale' => $locale,
                'mode' => $mode,
                'config' => $config,
            ];
        }

        return array_values($templates);
    }

    private function normalizeContentConfig(string $scene, string $channel, array $config, array $variableNames): array
    {
        $format = (string) ($config['format'] ?? NotificationTemplateFormat::TEXT);
        if (!in_array($format, NotificationTemplateFormat::all(), true)) {
            throw new \InvalidArgumentException('通知场景 ['.$scene.'] 的渠道 ['.$channel.'] 模板格式无效');
        }

        foreach (['subject', 'content'] as $field) {
            if (isset($config[$field]) && !is_string($config[$field])) {
                throw new \InvalidArgumentException('通知场景 ['.$scene.'] 的渠道 ['.$channel.'] '.$field.' 必须是字符串');
            }
        }
        if ('' === trim((string) ($config['subject'] ?? '')) && '' === trim((string) ($config['content'] ?? ''))) {
            throw new \InvalidArgumentException('通知场景 ['.$scene.'] 的渠道 ['.$channel.'] 内容模板不能为空');
        }

        foreach (['subject', 'content'] as $field) {
            foreach ($this->renderer->placeholders($config[$field] ?? null) as $placeholder) {
                if (!isset($variableNames[$placeholder])) {
                    throw new \InvalidArgumentException(
                        '通知场景 ['.$scene.'] 的渠道 ['.$channel.'] 模板引用了未声明变量 ['.$placeholder.']'
                    );
                }
            }
        }

        $config['format'] = $format;
        $config['subject'] = $config['subject'] ?? null;
        $config['content'] = $config['content'] ?? null;

        return $config;
    }

    private function normalizeReferenceConfig(string $scene, string $channel, array $config, array $variableNames): array
    {
        $config['template_key'] = $this->requiredString(
            $config['template_key'] ?? null,
            '通知场景 ['.$scene.'] 的渠道 ['.$channel.'] template_key',
            150
        );
        $mapping = $config['variable_map'] ?? [];
        if (!is_array($mapping)) {
            throw new \InvalidArgumentException('通知场景 ['.$scene.'] 的渠道 ['.$channel.'] variable_map 必须是数组');
        }

        foreach ($mapping as $target => $source) {
            if (!is_string($target) || '' === trim($target) || !is_string($source) || !isset($variableNames[$source])) {
                throw new \InvalidArgumentException(
                    '通知场景 ['.$scene.'] 的渠道 ['.$channel.'] variable_map 引用了无效变量'
                );
            }
        }
        $config['variable_map'] = $mapping;

        return $config;
    }

    private function normalizeChannels($channels, string $context): array
    {
        if (!is_array($channels) || [] === $channels) {
            throw new \InvalidArgumentException($context.' 的 default_channels 不能为空');
        }

        $normalized = [];
        foreach ($channels as $channel) {
            $channel = NotificationChannel::normalize((string) $channel);
            $normalized[$channel] = $channel;
        }

        return array_values($normalized);
    }

    /**
     * @param mixed $value
     */
    private function normalizeCode($value, string $field, int $maxLength, bool $allowSeparators = true): string
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException($field.' 必须是字符串');
        }

        $value = trim($value);
        $pattern = $allowSeparators
            ? '/\A[a-z][a-z0-9]*(?:[._-][a-z0-9]+)*\z/'
            : '/\A[a-z][a-z0-9_]*\z/';
        if ('' === $value || mb_strlen($value) > $maxLength || 1 !== preg_match($pattern, $value)) {
            throw new \InvalidArgumentException($field.' 格式无效');
        }

        return $value;
    }

    /**
     * @param mixed $value
     */
    private function requiredString($value, string $field, int $maxLength): string
    {
        if (!is_string($value) || '' === trim($value) || mb_strlen($value) > $maxLength) {
            throw new \InvalidArgumentException($field.' 不能为空且不能超过 '.$maxLength.' 个字符');
        }

        return trim($value);
    }

    private function normalizeLocale(string $locale): string
    {
        $parts = explode('-', str_replace('_', '-', trim($locale)));
        if ([] === $parts || '' === $parts[0]) {
            throw new \InvalidArgumentException('通知模板 locale 无效');
        }

        $parts[0] = strtolower($parts[0]);
        if (isset($parts[1]) && 2 === strlen($parts[1])) {
            $parts[1] = strtoupper($parts[1]);
        }

        $locale = implode('-', $parts);
        if (mb_strlen($locale) > 20 || 1 !== preg_match('/\A[a-z]{2,3}(?:-[A-Za-z0-9]{2,8})*\z/', $locale)) {
            throw new \InvalidArgumentException('通知模板 locale 无效');
        }

        return $locale;
    }
}
