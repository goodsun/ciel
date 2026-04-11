<?php
return [
    // Nav
    'nav_video' => 'Video',
    'nav_image' => 'Image',
    'nav_edit' => 'Edit',
    'nav_generated' => 'Generated',
    'login' => 'Login',
    'logout' => 'Logout',
    'terms_of_service' => 'Terms of Service',

    // Page titles
    'title_default' => 'Image Generation Service',
    'title_image' => 'Image Generator',
    'title_video' => 'Video Generator',
    'title_edit' => 'Image Editor',
    'title_generated' => 'Generated',
    'title_mypage' => 'My Page',
    'title_purchase_complete' => 'Purchase Complete',

    // LP
    'lp_subtitle' => 'AI Image & Video Generation Service',
    'lp_login' => 'Login with Google',

    // Common labels
    'prompt' => 'Prompt',
    'negative_prompt' => 'Negative Prompt',
    'width' => 'Width',
    'height' => 'Height',
    'steps' => 'Steps',
    'seed' => 'Seed',
    'cfg' => 'CFG',
    'jpeg_quality' => 'JPEG Quality',
    'seconds' => 'Seconds',
    'sec_suffix' => 's',
    'download' => 'Download',
    'generated' => 'Gallery',
    'log' => 'Log',

    // Image
    'generate' => 'Generate',
    'generating' => 'Generating...',
    'login_to_generate' => 'Login with Google to Generate',

    // Video
    'tab_i2v' => 'I2V (Image to Video)',
    'tab_flf2v' => 'FLF2V (Start/End Image to Video)',
    'input_image' => 'Input Image',
    'start_image' => 'Start Image',
    'end_image' => 'End Image',
    'drop_image' => 'Click or drag & drop to select image',
    'start_frame' => 'Start Frame',
    'end_frame' => 'End Frame',

    // Edit
    'edit_start' => 'Start Editing',
    'editing' => 'Editing...',
    'login_to_edit' => 'Login with Google to Edit',
    'source_image' => 'Source Image',
    'edit_instruction' => 'Edit Instruction',

    // Log messages
    'log_submitting' => 'Submitting job...',
    'log_job_id' => 'Job ID: ',
    'log_waiting' => 'Waiting for completion...',
    'log_status' => 'Status: ',
    'log_complete' => 'Done! Execution time: ',
    'log_failed' => 'Job failed: ',
    'log_request_error' => 'Request error: ',
    'log_polling_error' => 'Polling error: ',

    // Errors
    'err_pod_config' => 'Pod configuration missing',
    'err_enter_prompt' => 'Please enter a prompt',
    'err_select_image' => 'Please select an image',
    'err_select_both_images' => 'Please select start and end images',
    'err_select_source' => 'Please select a source image',
    'err_enter_instruction' => 'Please enter edit instructions',
    'err_insufficient' => 'Insufficient balance. Please purchase credits from My Page.',
    'err_error' => 'Error: ',
    'err_delete_failed' => 'Failed to delete',

    // Generated
    'no_generated' => 'No generated content yet.',
    'cost_estimate_notice' => 'Costs marked (est.) are estimated from recent usage. The final amount will be confirmed once billing data is available and may differ slightly.',
    'delete_confirm' => 'Delete this item?',

    // My Page
    'balance' => 'Balance',
    'member_since' => 'Member since',
    'purchase_credits' => 'Purchase Credits',
    'purchase' => 'Purchase',
    'agree_tos' => 'I have read and agree to the <a href="/service.php" target="_blank" style="color:#8bb4ff;">Terms of Service</a>',
    'agree_tos_required' => 'You must agree to the Terms of Service before purchasing.',
    'agree_tos_modal' => 'Please read the <a href="/service.php" target="_blank" style="color:#8bb4ff;">Terms of Service</a> and check the agreement checkbox before purchasing.',
    'recent_transactions' => 'Recent Transactions',
    'no_transactions' => 'No transactions yet.',
    'th_date' => 'Date',
    'th_type' => 'Type',
    'th_amount' => 'Amount',
    'th_balance' => 'Balance',
    'th_note' => 'Note',

    // Purchase Success
    'payment_received' => 'Payment Received',
    'payment_msg' => 'Your payment of <strong style="color:#e0e0e0;">$%s</strong> has been received.',
    'credits_soon' => 'Credits will be added to your balance shortly.',
    'go_mypage' => 'Go to My Page',

    // LoRA
    'lora_settings' => 'LoRA Settings',
    'lora_url' => 'LoRA URL',
    'lora_url_placeholder' => 'https://example.com/my-style.safetensors',
    'lora_strength' => 'LoRA Strength',
    'lora_none' => 'None (use default)',
    'lora_add' => '+ Add LoRA',
    'lora_remove' => 'Remove',

    // Content notice
    'content_notice' => 'If unintended inappropriate content was generated, please delete it from your gallery.',
    'tos_reminder' => 'By generating, you agree to the <a href="/service.php?lang=%s" target="_blank" style="color:var(--accent-bright,#a0bef0);">Terms of Service</a>.',

    // Footer
    'copyright' => 'copyright &copy; %s <a href="https://bon-soleil.com/" style="color:inherit;text-decoration:none;">bonsoleil</a>',
];
