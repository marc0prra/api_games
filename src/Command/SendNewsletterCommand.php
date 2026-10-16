<?php

namespace App\Command;

use App\Repository\UserRepository;
use App\Repository\VideoGameRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCronTask('30 8 * * 1', timezone: 'Europe/Paris')]
#[AsCommand(
    name: 'app:send-newsletter',
    description: 'Envoie la newsletter des jeux qui sortent dans les 7 prochains jours aux abonnés',
)]
class SendNewsletterCommand
{
    public function __construct(
        private UserRepository $userRepository,
        private VideoGameRepository $videoGameRepository,
        private MailerInterface $mailer,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $subscribers = $this->userRepository->findSubscribers();
        $games = $this->videoGameRepository->findUpcomingGames();

        foreach ($subscribers as $subscriber) {
            $email = (new TemplatedEmail())
                ->from('newsletter@api-games.fr')
                ->to($subscriber->getEmail())
                ->subject('Les sorties jeux vidéo de la semaine')
                ->htmlTemplate('emails/newsletter.html.twig')
                ->context(['games' => $games]);

            $this->mailer->send($email);
        }

        $io->success(sprintf('Newsletter envoyée à %d abonné(s) (%d jeu(x)).', count($subscribers), count($games)));

        return Command::SUCCESS;
    }
}
