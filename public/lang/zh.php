<?php
return [
    'html_lang' => 'zh',
    'title' => 'le ciel — AI 图像生成器',
    'lead' => 'your sky, your creation',
    'hero_cta' => '生成图像',
    'hero_cta_login' => 'Sign In',
    'scroll' => 'scroll',

    // Story 1
    'story1_catch' => '想象的宁静喜悦',
    'story1_body' => '将脑海中的风景直接化为现实。<br>le ciel 是一款贴合你想象力的 AI 图像生成工具。',

    // Story 2
    'story2_catch' => 'Why le ciel?',
    'story2_specs' => [
        '按 GPU 实际成本计费 — 无额外加价',
        '无需搭建环境 — 浏览器即可直接生成',
        '通过 URL 自由加载网络上的 LoRA',
        '在 Civitai 或 GitHub 发布自制 LoRA 即可使用',
        '一键切换多种 AI 模型',
        '完全控制 Steps、CFG、Seed 等关键参数',
        '新模型的理想验证平台',
    ],

    // Story 3
    'story3_catch' => '描绘属于你的天空',
    'cta' => '开始创作',
    'cta_sub' => '需要 Google 账号 / 18岁以上',

    // How it Works
    'how_title' => '使用流程',
    'step1_title' => '选择模型',
    'step1_desc' => '根据需求选择最佳 AI 模型和 LoRA',
    'step2_title' => '描述愿景',
    'step2_desc' => '通过提示词和参数传达理想图像',
    'step3_title' => '生成',
    'step3_desc' => '几秒内生成高质量图像',

    // Gallery
    'gallery_title' => 'le ciel 生成作品',
    'gallery_captions' => [
        'Anime / Forest scene / LoRA applied',
        'Anime / Kimono portrait / Custom model',
        'Anime / Fantasy portrait / Fine-tuned',
        'Anime / Night cityscape / Hi-res',
        'Anime / Ocean sunset / Stylized',
        'Anime / Mountain vista / Detailed',
    ],

    // FAQ
    'faq_title' => '常见问题',
    'faq' => [
        [
            'q' => '收费方式是什么？',
            'a' => '采用预付费制。通过 Stripe 购买美元额度，每次生成按实际 GPU 成本消耗。图像存储需另付月费。',
        ],
        [
            'q' => '注册需要什么？',
            'a' => '需要 Google 账号，且须年满18岁。',
        ],
        [
            'q' => '生成图像的版权归谁？',
            'a' => '生成图像的权利归用户所有，但须遵守所用 AI 模型的许可条款。详情请参阅<a href="service.php" style="color:var(--accent)">服务条款</a>。',
        ],
        [
            'q' => '支持哪些模型？',
            'a' => '支持 Stable Diffusion 等多种模型，可通过 LoRA 组合进行细致的风格调整。本服务作为生成式 AI 模型测试平台运营。',
        ],
    ],

    // Final CTA
    'final_catch' => '将想象变为现实',

    // Footer
    'terms' => '服务条款',
];
