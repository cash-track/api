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

    'password_verify_error' => 'Wrong password.',
    'unique_verify_error'   => 'Value should be unique.',

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

    'forgot_password_mail_subject' => 'Reset Your Password',
    'forgot_password_mail_hello'   => 'Hey',
    'forgot_password_mail_line_1'  => 'Someone has been requested password reset at',
    'forgot_password_mail_line_2'  => 'Click to the link bellow to continue process of resetting your password:',
    'forgot_password_mail_footer'  => 'If it\'s not you then someone trying to access your account. We\'re keeping your account safe, no worries.',

    'email_confirmation_mail_subject' => 'Confirm Your Account Email',
    'email_confirmation_mail_hello'   => 'Hey',
    'email_confirmation_mail_line_1'  => 'Your email was used to register at',
    'email_confirmation_mail_line_2'  => 'Click to the link bellow to confirm your account email:',
    'email_confirmation_mail_footer'  => 'If it\'s not you just ignore this.',

    'wallet_share_mail_subject'         => 'Invitation To The Wallet',
    'wallet_share_mail_hello'           => 'Hey',
    'wallet_share_mail_line_invited'    => 'invited you to wallet',
    'wallet_share_mail_line_invited_to' => 'at',
    'wallet_share_mail_line_2'          => 'Now you can use it together.',
    'wallet_share_mail_footer'          => 'If it\'s not you just ignore this.',

    'auth_no_free_nickname'             => 'Unable to allocate free nickname.',
    'google_auth_invalid_id_token'      => 'Token validation failed. Please try again.',
    'google_auth_id_token_not_verified' => 'Unexpected token verification response.',
    'google_auth_account_not_verified'  => 'Your Google account email is not verified. Please verify your Google account email first.',
    'google_auth_email_already_claimed' => 'Your email has been associated with other Google account. Please try different Google account.',

];
