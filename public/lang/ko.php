<?php
return [
    'html_lang' => 'ko',
    'title' => 'le ciel — AI 이미지 생성기',
    'lead' => 'your sky, your creation',
    'hero_cta' => '이미지 생성',
    'hero_cta_login' => 'Sign In',
    'scroll' => 'scroll',

    // Story 1
    'story1_catch' => '상상하는 고요한 기쁨',
    'story1_body' => '머릿속의 풍경을 그대로 형상화하세요.<br>le ciel은 당신의 상상력에 함께하는 AI 이미지 생성 도구입니다.',

    // Story 2
    'story2_catch' => 'Why le ciel?',
    'story2_specs' => [
        'GPU 실비 기반 종량제 — 불필요한 마진 없음',
        '환경 구축 불필요 — 브라우저에서 바로 생성',
        '웹상의 LoRA를 URL로 자유롭게 적용',
        'Civitai나 GitHub에 공개한 자작 LoRA도 사용 가능',
        '여러 AI 모델을 원클릭으로 전환',
        'Steps, CFG, Seed 등 주요 파라미터 완전 제어',
        '새 모델 검증 환경으로 최적',
    ],

    // Story 3
    'story3_catch' => '당신의 하늘을 그리세요',
    'cta' => '시작하기',
    'cta_sub' => 'Google 계정 필요 / 18세 이상',

    // How it Works
    'how_title' => '사용 방법',
    'step1_title' => '모델 선택',
    'step1_desc' => '목적에 맞는 최적의 AI 모델과 LoRA를 선택',
    'step2_title' => '비전 묘사',
    'step2_desc' => '프롬프트와 파라미터로 이상적인 이미지를 전달',
    'step3_title' => '생성',
    'step3_desc' => '몇 초 만에 고품질 이미지 생성',

    // Gallery
    'gallery_title' => 'le ciel 생성 작품',
    'gallery_captions' => [
        'Anime / Forest scene / LoRA applied',
        'Anime / Kimono portrait / Custom model',
        'Anime / Fantasy portrait / Fine-tuned',
        'Anime / Night cityscape / Hi-res',
        'Anime / Ocean sunset / Stylized',
        'Anime / Mountain vista / Detailed',
    ],

    // FAQ
    'faq_title' => 'FAQ',
    'faq' => [
        [
            'q' => '요금 체계는?',
            'a' => '선불제입니다. Stripe를 통해 USD 크레딧을 구매하고, 생성마다 실제 GPU 비용에 따라 소비됩니다. 이미지 저장에는 별도 월간 스토리지 요금이 부과됩니다.',
        ],
        [
            'q' => '가입에 필요한 것은?',
            'a' => 'Google 계정이 필요합니다. 18세 이상만 이용 가능합니다.',
        ],
        [
            'q' => '생성된 이미지의 권리는?',
            'a' => '생성된 이미지의 권리는 사용자에게 귀속되지만, 사용된 AI 모델의 라이선스 조건을 따릅니다. 자세한 내용은 <a href="service.php" style="color:var(--accent)">이용약관</a>을 확인하세요.',
        ],
        [
            'q' => '어떤 모델을 지원하나요?',
            'a' => 'Stable Diffusion을 비롯한 여러 모델을 지원하며, LoRA 조합으로 세밀한 스타일 조정이 가능합니다. 본 서비스는 생성 AI 모델 테스트 플랫폼으로 운영됩니다.',
        ],
    ],

    // Final CTA
    'final_catch' => '상상을 현실로',

    // Footer
    'terms' => '이용약관',
];
