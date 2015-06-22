<?php

// Necessário para herdar métodos padrão
return Jp7_Controller_Dispatcher::evalAsAController(__FILE__);

class Jp7_ContactController extends __Controller_Action
{
    public function indexAction()
    {
        include_once ROOT_PATH.'/inc/7.form.lib.php';

        $this->view->headScript()->appendFile('/_default/js/jquery/jquery.maskedinput-1.3.1.min.js');

        $contactTipo = self::getTipo();
        // Introdução
        if ($introductionTipo = $contactTipo->getFirstChildByModel('Introduction')) {
            $this->view->introductionItens = $introductionTipo->find(array(
                'fields' => '*',
            ));
        }

        // Formulário
        $record = null;

        // Recebeu POST
        if ($this->getRequest()->isPost()) {
            // Salvando registro
            try {
                $record = $this->_createRecord($contactTipo);
                $this->_validateAndSave($record);

                // Utilizado para preparar o email, não tem jeito melhor, por enquanto
                try {
                    $this->_sendEmail($record);
                    $this->_redirect($contactTipo->getUrl().'/ok');
                } catch (Exception $e2) {
                    throw new Exception('Problema ao enviar a mensagem. Por favor, tente novamente.');
                }
            } catch (Exception $e) {
                // Permite customizar mensagem de erro
                $this->view->errorMessage = $e->getMessage();
            }
        }

        // Construindo HTML do form
        $this->view->form = $this->_getFormHtml($contactTipo->getCampos(), $record);
    }

    protected function _validateAndSave($record)
    {
        foreach ($record->getTipo()->getCampos() as $campo) {
            $this->_validateCampo($record, $campo);
        }
        $record->save();
    }

    protected function _validateCampo($record, $campo)
    {
        if (!$campo['form']) {
            return;
        }

        return InterAdminField::validate($record, $campo);
    }

    protected function _createRecord()
    {
        $contactTipo = self::getTipo();
        $attributes = @array_map('reset', $_POST);

        $record = $contactTipo->createInterAdmin();
        $record->setAttributesSafely($attributes);

        return $record;
    }

    protected function _sendEmail($record, $sendReply = true)
    {
        $contactTipo = self::getTipo();
        $contactTipo->getFieldsValues('nome');
        $config = Zend_Registry::get('config');

        $recipients = $this->_getRecipients($contactTipo, $record);

        $formHelper = new Jp7_Form();
        // E-mail normal para os destinatários do site
        $mail = $formHelper->createMail($record, array(
            'subject' => 'Site '.$config->name.' - '.$contactTipo->nome,
            'title' => $contactTipo->nome,
            'recipients' => $recipients,
        ));
        $mail->setFrom($record->email, $record->name);
        $mail->send();

        if ($sendReply) {
            // E-mail de resposta para o usuário
            $reply = $formHelper->createMail($record, array(
                'subject' => 'Confirmação de Recebimento - '.$config->name.' - '.$contactTipo->nome,
                'title' => $contactTipo->nome,
                'recipients' => array($record), // Envia para o próprio usuário
                'message' => 'Agradecemos o seu contato.<br />'.
                    'Por favor, aguarde nosso retorno em breve.<br /><br />',
            ));
            $reply->setFrom($config->admin_email, $config->admin_name);
            $reply->send();
        }
    }

    protected function _getRecipients($contactTipo, $record)
    {
        $recipientsTipo = $contactTipo->getFirstChildByModel('ContactRecipients');

        return $recipientsTipo->find(array(
            'fields' => array('name', 'email'),
        ));
    }

    protected function _getFormHtml($campos, $record)
    {
        return InterAdminField::getForm($campos, $record);
    }

    public function okAction()
    {
        $this->view->title = 'Mensagem enviada com sucesso!';
        $this->view->message = 'Agradecemos o seu contato.<br />Por favor, aguarde nosso retorno em breve.';
    }
}
