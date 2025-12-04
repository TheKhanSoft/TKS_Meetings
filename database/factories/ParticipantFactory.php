<?php

namespace Database\Factories;

use App\Models\Participant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Participant>
 */
class ParticipantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Pakistani Context Data
        $firstNames = ['Muhammad', 'Ahmed', 'Ali', 'Fatima', 'Zainab', 'Bilal', 'Usman', 'Ayesha', 'Hamza', 'Hassan', 'Hussain', 'Sana', 'Hina', 'Sadia', 'Omar'];
        $lastNames = ['Khan', 'Malik', 'Butt', 'Chaudhry', 'Sheikh', 'Qureshi', 'Siddiqui', 'Raza', 'Gillani', 'Cheema', 'Bajwa', 'Jutt', 'Mirza'];
        
        $designations = ['Consultant', 'External Auditor', 'Guest Speaker', 'Industry Expert', 'Legal Advisor', 'Parent Representative', 'Alumni Representative', 'Community Leader', 'Educationist', 'Policy Maker', 'District Officer'];
        
        $organizations = ['Punjab University', 'UET Lahore', 'LUMS', 'HEC', 'FBR', 'State Bank of Pakistan', 'Wapda', 'PTCL', 'Civil Secretariat', 'District Government', 'Lahore High Court', 'Chamber of Commerce', 'Textile Mills Association'];

        $cities = ['Lahore', 'Karachi', 'Islamabad', 'Rawalpindi', 'Faisalabad', 'Multan', 'Peshawar', 'Quetta', 'Sialkot', 'Gujranwala'];
        $areas = ['Model Town', 'DHA', 'Gulberg', 'Johar Town', 'Cantt', 'Bahria Town', 'Saddar', 'F-8', 'G-10', 'Blue Area'];
        $titles = ['Mr.', 'Ms.', 'Dr.', 'Prof.', 'Prof. Dr.', 'Syed', 'Pir', 'Engr.', 'Justice (R)'];

        $firstName = $this->faker->randomElement($firstNames);
        $lastName = $this->faker->randomElement($lastNames);
        $name = "$firstName $lastName";
        
        $city = $this->faker->randomElement($cities);
        $area = $this->faker->randomElement($areas);
        $houseNo = $this->faker->numberBetween(1, 999);
        $streetNo = $this->faker->numberBetween(1, 50);
        
        $address = "House # $houseNo, Street $streetNo, $area, $city";
        $phone = "03" . $this->faker->numberBetween(10, 49) . "-" . $this->faker->numberBetween(1000000, 9999999);

        return [
            'title' => $this->faker->randomElement($titles),
            'name' => $name,
            'email' => strtolower($firstName . '.' . $lastName) . '@' . strtolower(str_replace(' ', '', $this->faker->randomElement($organizations))) . '.pk',
            'phone' => $phone,
            'address' => $address,
            'designation' => $this->faker->randomElement($designations),
            'organization' => $this->faker->randomElement($organizations),
        ];
    }
}
