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
    public function load(ObjectManager $manager): void
    {
        // Create Clients
        $client1 = new Client();
        $client1->setName('Miguel Goyena')
                ->setEmail('miguel_goyena@cuatrovientos.org')
                ->setType('premium');
        $manager->persist($client1);

        $client2 = new Client();
        $client2->setName('Ana García')
                ->setEmail('ana_garcia@email.com')
                ->setType('standard');
        $manager->persist($client2);

        $client3 = new Client();
        $client3->setName('Carlos López')
                ->setEmail('carlos_lopez@email.com')
                ->setType('standard');
        $manager->persist($client3);

        // Create Activities with Songs (future activities)
        $activity1 = new Activity();
        $activity1->setType('BodyPump')
                  ->setMaxParticipants(25)
                  ->setDateStart(new \DateTime('+1 day 10:00'))
                  ->setDateEnd(new \DateTime('+1 day 11:00'));
        
        $song1 = new Song();
        $song1->setName('Pump It Up')->setDurationSeconds(245);
        $activity1->addSong($song1);
        
        $song2 = new Song();
        $song2->setName('Eye of the Tiger')->setDurationSeconds(280);
        $activity1->addSong($song2);
        
        $manager->persist($activity1);

        $activity2 = new Activity();
        $activity2->setType('Spinning')
                  ->setMaxParticipants(20)
                  ->setDateStart(new \DateTime('+2 days 18:00'))
                  ->setDateEnd(new \DateTime('+2 days 19:00'));
        
        $song3 = new Song();
        $song3->setName('Push It')->setDurationSeconds(210);
        $activity2->addSong($song3);
        
        $manager->persist($activity2);

        $activity3 = new Activity();
        $activity3->setType('Core')
                  ->setMaxParticipants(15)
                  ->setDateStart(new \DateTime('+3 days 09:00'))
                  ->setDateEnd(new \DateTime('+3 days 09:45'));
        
        $song4 = new Song();
        $song4->setName('Core Power')->setDurationSeconds(180);
        $activity3->addSong($song4);
        
        $manager->persist($activity3);

        // Create a full activity (no free places)
        $activityFull = new Activity();
        $activityFull->setType('BodyPump')
                     ->setMaxParticipants(2)
                     ->setDateStart(new \DateTime('+4 days 10:00'))
                     ->setDateEnd(new \DateTime('+4 days 11:00'));
        $manager->persist($activityFull);

        // Create past activity for statistics
        $activityPast = new Activity();
        $activityPast->setType('Spinning')
                     ->setMaxParticipants(20)
                     ->setDateStart(new \DateTime('-30 days 18:00'))
                     ->setDateEnd(new \DateTime('-30 days 19:00'));
        $manager->persist($activityPast);

        $activityPast2 = new Activity();
        $activityPast2->setType('Core')
                      ->setMaxParticipants(15)
                      ->setDateStart(new \DateTime('-60 days 09:00'))
                      ->setDateEnd(new \DateTime('-60 days 09:45'));
        $manager->persist($activityPast2);

        $manager->flush();

        // Create bookings
        $booking1 = new Booking();
        $booking1->setClient($client1)->setActivity($activity1);
        $manager->persist($booking1);

        $booking2 = new Booking();
        $booking2->setClient($client2)->setActivity($activity1);
        $manager->persist($booking2);

        $booking3 = new Booking();
        $booking3->setClient($client1)->setActivity($activity2);
        $manager->persist($booking3);

        // Fill the full activity
        $bookingFull1 = new Booking();
        $bookingFull1->setClient($client2)->setActivity($activityFull);
        $manager->persist($bookingFull1);

        $bookingFull2 = new Booking();
        $bookingFull2->setClient($client3)->setActivity($activityFull);
        $manager->persist($bookingFull2);

        // Past bookings for statistics
        $bookingPast1 = new Booking();
        $bookingPast1->setClient($client1)->setActivity($activityPast);
        $manager->persist($bookingPast1);

        $bookingPast2 = new Booking();
        $bookingPast2->setClient($client1)->setActivity($activityPast2);
        $manager->persist($bookingPast2);

        $manager->flush();
    }
}
