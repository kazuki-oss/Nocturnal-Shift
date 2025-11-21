<?php

namespace App\DataFixtures;

use App\Entity\EmployeeProfile;
use App\Entity\Permission;
use App\Entity\Role;
use App\Entity\Tenant;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // 1. Create Tenant
        $tenant = new Tenant();
        $tenant->setName('Demo Bar Tokyo');
        $tenant->setDomain('localhost'); // For local testing
        $manager->persist($tenant);

        // 2. Create Permissions
        $permissions = [
            'view_dashboard' => 'View Dashboard',
            'manage_schedule' => 'Manage Schedule',
            'manage_employees' => 'Manage Employees',
            'submit_shift' => 'Submit Shift',
            'view_own_shifts' => 'View Own Shifts',
        ];

        $permissionEntities = [];
        foreach ($permissions as $identifier => $name) {
            $permission = new Permission();
            $permission->setIdentifier($identifier);
            $permission->setName($name);
            $manager->persist($permission);
            $permissionEntities[$identifier] = $permission;
        }

        // 3. Create Roles
        $adminRole = new Role();
        $adminRole->setIdentifier('ROLE_ADMIN');
        $adminRole->setName('Administrator');
        $adminRole->addPermission($permissionEntities['view_dashboard']);
        $adminRole->addPermission($permissionEntities['manage_schedule']);
        $adminRole->addPermission($permissionEntities['manage_employees']);
        $manager->persist($adminRole);

        $employeeRole = new Role();
        $employeeRole->setIdentifier('ROLE_USER'); // Standard Symfony role for logged in users
        $employeeRole->setName('Employee');
        $employeeRole->addPermission($permissionEntities['view_dashboard']);
        $employeeRole->addPermission($permissionEntities['submit_shift']);
        $employeeRole->addPermission($permissionEntities['view_own_shifts']);
        $manager->persist($employeeRole);

        // 4. Create Admin User
        $adminUser = new User();
        $adminUser->setEmail('admin@example.com');
        $adminUser->setName('Admin User');
        $adminUser->setTenant($tenant);
        $adminUser->setRoles(['ROLE_ADMIN']); // Symfony roles
        $adminUser->addUserRole($adminRole); // Our dynamic role
        $hashedPassword = $this->passwordHasher->hashPassword($adminUser, 'password');
        $adminUser->setPassword($hashedPassword);
        $manager->persist($adminUser);

        // 5. Create Employee User
        $employeeUser = new User();
        $employeeUser->setEmail('employee@example.com');
        $employeeUser->setName('John Doe');
        $employeeUser->setTenant($tenant);
        $employeeUser->setRoles(['ROLE_USER']);
        $employeeUser->addUserRole($employeeRole);
        $hashedPasswordEmp = $this->passwordHasher->hashPassword($employeeUser, 'password');
        $employeeUser->setPassword($hashedPasswordEmp);
        
        // Employee Profile
        $profile = new EmployeeProfile();
        $profile->setHireDate(new \DateTime('2024-01-01'));
        $profile->setHourlyRate(1500);
        $profile->setUser($employeeUser);
        $manager->persist($profile);
        
        $manager->persist($employeeUser);

        $manager->flush();
    }
}
