<?php

namespace App\Command;

use App\Entity\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


class CreateuserCommand extends Command
{
    protected static $defaultName = 'app:createuser';

    private $container;
    private $encoder;

    public function __construct(ContainerInterface $container, UserPasswordEncoderInterface $encoder)
    {
        parent::__construct();
        $this->container = $container;
        $this->encoder = $encoder;
    }
    protected function configure()
    {
        $this
            ->setDescription('Create a user')
        ;
    }

    protected function retrieveUsername(InputInterface $input, OutputInterface $output): string
    {
        $questionUsername = new Question('Username : ');
        $questionUsername->setValidator(function ($value) {
            if (trim($value) == '') {
                throw new \Exception('The username cannot be empty');
            }

            return $value;
        });
        return $this->getHelper('question')->ask($input, $output, $questionUsername);
    }

    protected function retrievePassword(InputInterface $input, OutputInterface $output): string
    {
        $questionPassword = new Question('Password : ');
        $questionPassword->setHidden(true);
        $questionPassword->setValidator(function ($value) {
            if (trim($value) == '') {
                throw new \Exception('The password cannot be empty');
            }
            return $value;
        });
        return $this->getHelper('question')->ask($input, $output, $questionPassword);
    }

    protected function retrieveEmail(InputInterface $input, OutputInterface $output): string
    {
        $questionEmail = new Question('Email : ');
        $questionEmail->setValidator(function ($value) {
            if (trim($value) == '') {
                throw new \Exception('The email cannot be empty');
            }
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('This is not a valid email');
            }
            return $value;
        });
        return $this->getHelper('question')->ask($input, $output, $questionEmail);

    }

    protected function retrieveRole(InputInterface $input, OutputInterface $output)
    {

        $questionRole = new ChoiceQuestion(
            'Role : ',
            ['User', 'Admin'],
            0
        );
        $role = $this->getHelper('question')->ask($input, $output, $questionRole);

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $em = $this->container->get('doctrine')->getManager();
        $user = new User();
        $output->writeln([
            'User Creator',
            '============',
            '',
        ]);
        $user->setUsername($this->retrieveUsername($input, $output));
        $plainPassword = $this->retrievePassword($input, $output);
        $password = $this->encoder->encodePassword($user, $plainPassword);
        $user->setPassword($password);
        $user->setEmail($this->retrieveEmail($input, $output));
        $role = $this->retrieveRole($input, $output);
        if ($role == 'User')
            $user->setRoles(['ROLE_USER']);
        else
            $user->setRoles(['ROLE_ADMIN']);
        $em->persist($user);
        $em->flush();

        return 0;
    }
}
