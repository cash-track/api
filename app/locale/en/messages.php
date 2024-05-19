<?php

return [
    'error_authentication_required'         => 'Authentication required.',
    'error_authentication_failure'          => 'Wrong email or password.',
    'error_token_authentication_failure'    => 'Invalid access token.',
    'error_authentication_exception'        => 'Unable to authenticate. Please try again later.',
    'error_unknown_currency'                => 'Unable to find currency.',
    'error_loading_default_currency'        => 'Unable to load default currency.',
    'error_password_confirmation_not_match' => 'The password confirmation does not match.',
    'error_wallet_does_not_exists'          => 'One or more wallets does not exists.',
    'error_nick_name_claimed'               => 'Nick name already claimed.',
    'error_value_is_not_unique'             => 'Value should be unique.',
    'error_profile_not_confirmed'           => 'You are not allowed to perform this action as your profile is not confirmed.',
    'error_rate_limit_reached'              => 'Too many requests. Please try again later.',
    'error_authentication_passkey'          => 'Passkey validation failed. Please try again.',
    'error_auth_passkey_invalid_challenge'  => 'Authentication attempt is not verified. Please try again.',
    'error_auth_passkey_invalid_response'   => 'Authentication response is not valid. Please try again.',
    'error_auth_passkey_unregistered'       => 'Provided passkey is not registered. Please use passkey added before.',

    'email_confirmation_confirm_failure' => 'Unable to confirm your email.',
    'email_confirmation_ok'              => 'Your email has been confirmed.',
    'email_confirmation_resend_failure'  => 'Error on trying to send new confirmation link.',
    'email_confirmation_resend_ok'       => 'Confirmation message has been sent.',

    'forgot_password_send_failure'  => 'Unable to reset your password.',
    'forgot_password_sent'          => 'Email with reset password link has been sent.',
    'forgot_password_reset_failure' => 'Unable to reset your password.',
    'forgot_password_reset_ok'      => 'Your password has been changed.',

    'user_register_exception' => 'Unable to register new user. Please try again later.',
    'nick_name_register_free' => 'Nick name are free to register',

    'password_change_exception' => 'Unable to update user password. Please try again later.',
    'password_change_ok'        => 'Password has been changed.',

    'profile_photo_update_empty'     => 'Unable to store uploaded photo. Please try again later.',
    'profile_photo_update_exception' => 'Unable to update user photo. Please try again later.',
    'profile_photo_update_ok'        => 'Photo has been updated.',

    'profile_nick_name_free'          => 'Nick name are free to use',
    'profile_update_locale_exception' => 'Unable to update user locale. Please try again later.',
    'profile_update_basic_exception'  => 'Unable to update basic user profile. Please try again later.',

    'tag_create_exception' => 'Unable to create new tag. Please try again later.',
    'tag_update_exception' => 'Unable to update tag. Please try again later.',
    'tag_delete_exception' => 'Unable to delete tag. Please try again later.',

    'charge_create_exception' => 'Unable to create charge. Please try again later.',
    'charge_update_exception' => 'Unable to update charge. Please try again later.',
    'charge_delete_exception' => 'Unable to delete charge. Please try again later.',

    'wallet_activate_exception'   => 'Unable to activate wallet. Please try again later.',
    'wallet_disable_exception'    => 'Unable to disable wallet. Please try again later.',
    'wallet_archive_exception'    => 'Unable to archive wallet. Please try again later.',
    'wallet_un_archive_exception' => 'Unable to un-archive wallet. Please try again later.',
    'wallet_share_exception'      => 'Unable to share wallet with user. Please try again later.',
    'wallet_revoke_exception'     => 'Unable to revoke user from wallet. Please try again later.',
    'wallet_revoke_owner'         => 'Unable to revoke user from wallet. You are only one member. Delete wallet if you do not need them anymore.',
    'wallet_revoke_owner_error'   => 'Current user is the one wallet owner.',
    'wallet_create_exception'     => 'Unable to create new wallet. Please try again later.',
    'wallet_update_exception'     => 'Unable to update wallet. Please try again later.',
    'wallet_delete_exception'     => 'Unable to delete wallet. Please try again later.',

    'limit_create_exception' => 'Unable to create limit. Please try again later.',
    'limit_update_exception' => 'Unable to update limit. Please try again later.',
    'limit_delete_exception' => 'Unable to delete limit. Please try again later.',

    'password_verify_error' => 'Wrong password.',
    'unique_verify_error'   => 'Value should be unique.',

    'email_footer'      => 'Made with ❤️ &nbsp;in 🇺🇦',
    'email_footer_help' => 'Help',

    'email_confirmation_account_already_confirmed' => 'You already confirmed your account email.',
    'email_confirmation_throttled'                 => 'Previous confirmation is already sent less than %d seconds ago.',
    'email_confirmation_invalid_token'             => 'Wrong confirmation token.',
    'email_confirmation_expired'                   => 'Confirmation link are expired.',
    'email_confirmation_invalid_user'              => 'Unable to find user linked to confirmation link.',

    'forgot_password_invalid_user' => 'Unable to find user by email.',
    'forgot_password_throttled'    => 'Previous request was created in less than %d seconds.',
    'forgot_password_invalid_code' => 'Wrong password reset code',
    'forgot_password_expired'      => 'Password reset link are expired',
    'forgot_password_missing_user' => 'Unable to find user linked to password reset link',

    'test_mail_subject' => 'Test mail',
    'test_mail_hello'   => 'Welcome',
    'test_mail_line_1'  => 'This is a test mail.',
    'test_mail_line_2'  => 'Sending mails are working well.',
    'test_mail_footer'  => 'Have a nice day.',

    'forgot_password_mail_subject'        => 'Reset Your Password',
    'forgot_password_mail_hello'          => 'Hey',
    'forgot_password_mail_line_1'         => 'Seems you\'re trying to reset your password.',
    'forgot_password_mail_line_2'         => 'Click to the link bellow to set new password:',
    'forgot_password_mail_reset_password' => 'Set New Password',
    'forgot_password_mail_footer'         => 'If it\'s not you then someone trying to access your account. We\'ll keep your account safe, no worries.',

    'email_confirmation_mail_subject' => 'Confirm Your Account Email',
    'email_confirmation_mail_hello'   => 'Hey',
    'email_confirmation_mail_line_1'  => 'Your email was used to register new account',
    'email_confirmation_mail_line_2'  => 'Follow to the link bellow to confirm your account email:',
    'email_confirmation_mail_confirm' => 'Confirm',
    'email_confirmation_mail_footer'  => 'If it\'s not you just ignore this message.',

    'wallet_share_mail_subject'         => 'Invitation To The Wallet',
    'wallet_share_mail_hello'           => 'Hey',
    'wallet_share_mail_line_invited'    => 'invited you to wallet',
    'wallet_share_mail_line_2'          => 'Now you can use it together. You will be able to manage income and expenses using common tags and many more.',
    'wallet_share_mail_check_wallet'    => 'Check Wallet',
    'wallet_share_mail_footer'          => 'If it\'s not you just ignore this.',

    'welcome_mail_subject'              => 'Welcome on Cash Track! 👋',
    'welcome_mail_line_2'               => 'Your journey just begins. Discover the world of clarity about finances with us.',
    'welcome_mail_line_3'               => 'Go ahead and create your first Wallet.',
    'welcome_mail_create_wallet'        => 'Create Wallet',
    'welcome_mail_line_4_1'             => 'Follow our',
    'welcome_mail_line_4_telegram_link' => 'Telegram Channel',
    'welcome_mail_line_4_2'             => 'for new feature updates, cash tracking tips & tricks.',
    'welcome_mail_telegram_channel'     => 'Telegram Channel',
    'welcome_mail_line_5_1'             => 'We hope you\'ll enjoy using Cash Track. Follow',
    'welcome_mail_line_5_about_link'    => 'About',
    'welcome_mail_line_5_2'             => 'page in case any questions.',

    'auth_no_free_nickname'             => 'Unable to allocate free nickname.',
    'google_auth_invalid_id_token'      => 'Token validation failed. Please try again.',
    'google_auth_id_token_not_verified' => 'Unexpected token verification response.',
    'google_auth_account_not_verified'  => 'Your Google account email is not verified. Please verify your Google account email first.',
    'google_auth_email_already_claimed' => 'Your email has been associated with other Google account. Please try different Google account.',

    'telegram_channel_nl_mail_subject'     => 'Telegram Channel',
    'telegram_channel_nl_mail_hello'       => 'Greetings',
    'telegram_channel_nl_mail_line_1'      => 'We\'d like to inform you about latest updates. Recently we\'ve started a new communication channel on Telegram.',
    'telegram_channel_nl_mail_line_2'      => 'If you are an active user - feel free to join our new',
    'telegram_channel_nl_mail_line_2_link' => 'channel',
    'telegram_channel_nl_mail_join'        => 'Join',
    'telegram_channel_nl_mail_line_3'      => 'There we\'re going to publish:',
    'telegram_channel_nl_mail_line_3_1'    => 'important platform updates',
    'telegram_channel_nl_mail_line_3_2'    => 'existing features overview',
    'telegram_channel_nl_mail_line_3_3'    => 'future plans / roadmap',
    'telegram_channel_nl_mail_line_3_4'    => 'financial tips and advices',
    'telegram_channel_nl_mail_line_4'      => 'Thank you for staying with us.',
    'telegram_channel_nl_mail_line_5'      => 'Cheers',

    'deletion_notice_nl_mail_subject'       => 'Deletion Notice',
    'deletion_notice_nl_mail_hello'         => 'Greetings',
    'deletion_notice_nl_mail_line_1'        => 'Your email have been used to create an account at Cash Track some time ago. We\'ve found that the email address is not confirmed yet.',
    'deletion_notice_nl_mail_line_2'        => 'As a part of our retention policy, we\'re checking old unused accounts to inform possible owners about upcoming deletion activity.',
    'deletion_notice_nl_mail_line_3_1'      => 'You account will be automatically deleted within 3 months since today.',
    'deletion_notice_nl_mail_line_3_2'      => 'By term "Account" means your user profile and all related data including your personal details, wallets, charges.',
    'deletion_notice_nl_mail_line_4'        => 'In case this is a mistake, you are an active Cash Track user, but for some reasons still didn\'t confirm your email address - do the next:',
    'deletion_notice_nl_mail_line_4_1_link' => 'Login',
    'deletion_notice_nl_mail_line_4_1'      => 'to your account',
    'deletion_notice_nl_mail_line_4_2'      => 'Go to your',
    'deletion_notice_nl_mail_line_4_2_link' => 'Profile Settings',
    'deletion_notice_nl_mail_line_4_3'      => 'Near email address click "Resend"',
    'deletion_notice_nl_mail_line_4_4'      => 'Check your mailbox for a Confirmation Mail',
    'deletion_notice_nl_mail_line_4_5'      => 'Follow instructions',
    'deletion_notice_nl_mail_line_5'        => 'If you have any issues on confirming your account, please respond to this message with the details. We will try to help you.',
    'deletion_notice_nl_mail_line_6'        => 'If you didn\'t register at Cash Track or don\'t want to use the service anymore - feel free to ignore this message. Your data will be wiped soon.',
    'deletion_notice_nl_mail_line_7'        => 'Sincerely,',
    'deletion_notice_nl_mail_line_8'        => 'Cash Track Support',

    'passkey_deleted' => 'Passkey has been deleted',
    'passkey_init_exception' => 'Unable to initiate passkey creation. Please try again later.',
    'passkey_store_exception' => 'Unable to store passkey. Please try again later.',
    'passkey_delete_exception' => 'Unable to delete passkey. Please try again later.',
];
