<?php

namespace App\DataFixtures;

use App\Entity\Activity;
use App\Entity\Booking;
use App\Entity\Client;
use App\Entity\Song;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    private ObjectManager $manager;

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;

        $clients = $this->createClients();
        $activities = $this->createActivities();
        $this->manager->flush();

        $this->createBookings($clients, $activities);
        $this->manager->flush();
    }

    private function createClients(): array
    {
        return [
            'premium' => $this->createClient('Miguel Goyena', 'miguel_goyena@cuatrovientos.org', Client::TYPE_PREMIUM),
            'standard1' => $this->createClient('Ana García', 'ana_garcia@email.com', Client::TYPE_STANDARD),
            'standard2' => $this->createClient('Carlos López', 'carlos_lopez@email.com', Client::TYPE_STANDARD),
        ];
    }

    private function createClient(string $name, string $email, string $type): Client
    {
        $client = new Client();
        $client->setName($name)
               ->setEmail($email)
               ->setType($type);

        $this->manager->persist($client);

        return $client;
    }

    private function createActivities(): array
    {
        return [
            'bodyPump' => $this->createBodyPumpActivity(),
            'spinning' => $this->createSpinningActivity(),
            'core' => $this->createCoreActivity(),
            'full' => $this->createFullActivity(),
            'past1' => $this->createPastSpinningActivity(),
            'past2' => $this->createPastCoreActivity(),
        ];
    }

    private function createBodyPumpActivity(): Activity
    {
        $activity = $this->createActivity(
            Activity::TYPE_BODYPUMP,
            25,
            '+1 day 10:00',
            '+1 day 11:00'
        );

        $this->addSongToActivity($activity, 'Pump It Up', 245);
        $this->addSongToActivity($activity, 'Eye of the Tiger', 280);

        return $activity;
    }

    private function createSpinningActivity(): Activity
    {
        $activity = $this->createActivity(
            Activity::TYPE_SPINNING,
            20,
            '+2 days 18:00',
            '+2 days 19:00'
        );

        $this->addSongToActivity($activity, 'Push It', 210);

        return $activity;
    }

    private function createCoreActivity(): Activity
    {
        $activity = $this->createActivity(
            Activity::TYPE_CORE,
            15,
            '+3 days 09:00',
            '+3 days 09:45'
        );

        $this->addSongToActivity($activity, 'Core Power', 180);

        return $activity;
    }

    private function createFullActivity(): Activity
    {
        return $this->createActivity(
            Activity::TYPE_BODYPUMP,
            2,
            '+4 days 10:00',
            '+4 days 11:00'
        );
    }

    private function createPastSpinningActivity(): Activity
    {
        return $this->createActivity(
            Activity::TYPE_SPINNING,
            20,
            '-30 days 18:00',
            '-30 days 19:00'
        );
    }

    private function createPastCoreActivity(): Activity
    {
        return $this->createActivity(
            Activity::TYPE_CORE,
            15,
            '-60 days 09:00',
            '-60 days 09:45'
        );
    }

    private function createActivity(string $type, int $maxParticipants, string $startOffset, string $endOffset): Activity
    {
        $activity = new Activity();
        $activity->setType($type)
                 ->setMaxParticipants($maxParticipants)
                 ->setDateStart(new \DateTime($startOffset))
                 ->setDateEnd(new \DateTime($endOffset));

        $this->manager->persist($activity);

        return $activity;
    }

    private function addSongToActivity(Activity $activity, string $name, int $duration): void
    {
        $song = new Song();
        $song->setName($name)
             ->setDurationSeconds($duration);

        $activity->addSong($song);
    }

    private function createBookings(array $clients, array $activities): void
    {
        $this->createFutureBookings($clients, $activities);
        $this->createFullActivityBookings($clients, $activities);
        $this->createPastBookingsForStatistics($clients, $activities);
    }

    private function createFutureBookings(array $clients, array $activities): void
    {
        $this->createBooking($clients['premium'], $activities['bodyPump']);
        $this->createBooking($clients['standard1'], $activities['bodyPump']);
        $this->createBooking($clients['premium'], $activities['spinning']);
    }

    private function createFullActivityBookings(array $clients, array $activities): void
    {
        $this->createBooking($clients['standard1'], $activities['full']);
        $this->createBooking($clients['standard2'], $activities['full']);
    }

    private function createPastBookingsForStatistics(array $clients, array $activities): void
    {
        $this->createBooking($clients['premium'], $activities['past1']);
        $this->createBooking($clients['premium'], $activities['past2']);
    }

    private function createBooking(Client $client, Activity $activity): void
    {
        $booking = new Booking();
        $booking->setClient($client)
                ->setActivity($activity);

        $this->manager->persist($booking);
    }
}
