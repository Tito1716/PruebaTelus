<?php

use App\Handler\HandlerInterface;
use League\Csv\Writer;

class GenerateFiles implements HandlerInterface
{
    private $sftpUsername;
    private $sftpPassword;
    private $next;

    public function __construct(string $sftpUsername, string $sftpPassword)
    {
        $this->sftpUsername = $sftpUsername;
        $this->sftpPassword = $sftpPassword;
    }

    public function setNext(HandlerInterface $handler): HandlerInterface
    {
        $this->next = $handler;
        return $handler;
    }

    public function handle(array $data): ?array
    {
        $currentDate = new \DateTime();
        $formattedDate = $currentDate->format('Y-m-d');

        $jsonFileName = 'users_' . $formattedDate . '.json';
        // Save the data locally as JSON
        file_put_contents($jsonFileName, json_encode($data, JSON_PRETTY_PRINT));

        $csvFileName = 'ETL' . $formattedDate . '.csv';

        // Process the JSON data
        $statistics = $this->processJsonData($data);

        //Save Raw CSV
        $this->convertJsonToCsv($jsonFileName, $csvFileName);
        // Save statistics to CSV
        $this->saveStatisticsToCsv($statistics, $csvFileName);



        // Clean up local file
        unlink($csvFileName);

        if ($this->next) {
            return $this->next->handle([$jsonFileName, $csvFileName, $statistics]);
        }

        return null;
    }

    private function convertJsonToCsv(string $jsonFile, string $csvFile): void
    {
        $jsonData = json_decode(file_get_contents($jsonFile), true);

        if (isset($jsonData['users']) && is_array($jsonData['users'])) {
            $users = $jsonData['users'];
        } else {
            $users = $jsonData; // Assume the entire array is users data
        }

        $csv = Writer::createFromPath($csvFile, 'w+');

        // Write the header
        if (!empty($users)) {
            $csv->insertOne(array_keys($users[0]));
        }

        // Write the data
        foreach ($users as $user) {
            $csv->insertOne($user);
        }
    }
    private function processJsonData(array $data): array
    {
        $users = $data['users'];
        $totalUsers = count($users);
        $genderCount = ['male' => 0, 'female' => 0];
        $ageGroups = [
            '00-10' => 0, '11-20' => 0, '21-30' => 0, '31-40' => 0,
            '41-50' => 0, '51-60' => 0, '61-70' => 0, '71-80' => 0,
            '81-90' => 0, '91+' => 0
        ];
        $cityStats = [];

        foreach ($users as $user) {
            // Gender count
            $genderCount[$user['gender']]++;

            // Age groups
            $ageGroup = $this->getAgeGroup($user['age']);
            $ageGroups[$ageGroup]++;

            // City statistics
            $city = $user['address']['city'];
            if (!isset($cityStats[$city])) {
                $cityStats[$city] = ['total' => 0, 'male' => 0, 'female' => 0];
            }
            $cityStats[$city]['total']++;
            $cityStats[$city][$user['gender']]++;
        }

        return [
            'totalUsers' => $totalUsers,
            'genderCount' => $genderCount,
            'ageGroups' => $ageGroups,
            'cityStats' => $cityStats
        ];
    }

    private function getAgeGroup(int $age): string
    {
        $groups = [
            '00-10', '11-20', '21-30', '31-40', '41-50',
            '51-60', '61-70', '71-80', '81-90', '91+'
        ];

        $index = min(floor($age / 10), 9);
        return $groups[$index];
    }

    private function saveStatisticsToCsv(array $statistics, string $csvFileName): void
    {
        $csv = Writer::createFromPath($csvFileName, 'w+');

        // Total users
        $csv->insertOne(['Total Users', $statistics['totalUsers']]);
        $csv->insertOne([]);

        // Gender count
        $csv->insertOne(['Gender', 'Count']);
        foreach ($statistics['genderCount'] as $gender => $count) {
            $csv->insertOne([$gender, $count]);
        }
        $csv->insertOne([]);

        // Age groups
        $csv->insertOne(['Age Group', 'Count']);
        foreach ($statistics['ageGroups'] as $group => $count) {
            $csv->insertOne([$group, $count]);
        }
        $csv->insertOne([]);

        // City statistics
        $csv->insertOne(['City', 'Total', 'Male', 'Female']);
        foreach ($statistics['cityStats'] as $city => $stats) {
            $csv->insertOne([$city, $stats['total'], $stats['male'], $stats['female']]);
        }
    }
}
