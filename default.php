<?php

/**
 * Disallows users to register with a disposable mail address.
 *
 * Checks mail address during registration process and will return a validation
 * error if mail provider is not on the list of allowed mail domains.
 *
 * @package mailfilter
 * @author Tobias Herbold
 * @license MIT
 */
class MailFilterPlugin extends Gdn_Plugin
{
    /**
     * Allow updating the list of domains manually.
     *
     * @param settingsController $sender Instance of the calling class.
     * @return void.
     * @since 0.1
     * @package mailfilter
     */
    public function settingsController_mailfilter_create($sender): void
    {
        $sender->permission('Garden.Settings.Manage');
        $sender->setData('Title', t('Mail Filter Settings'));
        $sender->setHighlightRoute('dashboard/settings/plugins');

        $conf = new ConfigurationModule($sender);
        $conf->initialize([
            'Plugins.mailfilter.allowed_domains' => [
                'Description' => t('Domains'),
                'Default' => 'gmail.com',
                'LabelCode' => t('List Domains with an comma between.')
            ]
        ]);

        $conf->renderAll();;
    }

    /**
     * Disallow users to register with not allowed email domains.
     *
     * @param object $sender UserModel.
     * @param mixed $args EventArguments of BeforeRegister.
     * @return void.
     * @since 0.1
     * @package mailfilter
     */
    public function UserModel_BeforeRegister_Handler($sender, &$args): void
    {
        // Get mail provider from form.
        if (isset($args['RegisteringUser'])) {
            $email = $args['RegisteringUser']['Email'];
        } else {
            $email = $args['User']['Email'];
        }

        // Return if no vaild mail.
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }


        // Try to get most recent list which is held in cache folder.
        $allowed_domains = Gdn::config(
            'Plugins.mailfilter.allowed_domains',
            ''
        );
        $allowed_domains = explode(",", $allowed_domains);

        // Get lowercase domain from email.
        if (strrpos($email, '@') < 1) {
            return;
        }

        $domain = explode("@", $email)[1];

        // Return if domain is listed.
        if (in_array($domain, $allowed_domains)) {
            return;
        }

        // Set error message.
        $sender->Validation->addValidationResult(
            'Email',
            'Your mail domain is not allowed.'
        );

        $args['Valid'] = false;
    }
}
