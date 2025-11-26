<?php

namespace App\Service;

use App\Entity\Shift;
use App\Entity\Tenant;
use App\Repository\ShiftRepository;
use App\Repository\ShiftTemplateRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class ShiftGeneratorService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ShiftTemplateRepository $templateRepository,
        private UserRepository $userRepository,
        private ShiftRepository $shiftRepository
    ) {}

    public function generateShifts(Tenant $tenant, \DateTimeInterface $start, \DateTimeInterface $end): int
    {
        $templates = $this->templateRepository->findBy(['tenant' => $tenant]);
        $users = $this->userRepository->findBy(['tenant' => $tenant]);
        
        if (empty($templates) || empty($users)) {
            return 0;
        }

        $generatedCount = 0;
        $current = clone $start;

        while ($current <= $end) {
            $dayOfWeek = (int)$current->format('w');

            foreach ($templates as $template) {
                if (in_array($dayOfWeek, $template->getApplicableDays())) {
                    // 必要な人数分シフトを作成
                    // 簡易ロジック: ランダムにユーザーを割り当て（実際はスキルや希望を考慮すべきだが今回は簡易版）
                    // 既存のシフトがあるユーザーは除外
                    
                    $assignedCount = 0;
                    $availableUsers = $users;
                    shuffle($availableUsers); // ランダム性

                    foreach ($availableUsers as $user) {
                        if ($assignedCount >= $template->getRequiredStaffCount()) {
                            break;
                        }

                        // 既にこの日にシフトが入っているか確認
                        $existingShift = $this->shiftRepository->findOneBy([
                            'user' => $user,
                            'shiftDate' => $current
                        ]);

                        if (!$existingShift) {
                            $shift = new Shift();
                            $shift->setTenant($tenant);
                            $shift->setUser($user);
                            $shift->setShiftDate(clone $current);
                            
                            $startTime = clone $current;
                            $startTime->setTime(
                                (int)$template->getStartTime()->format('H'),
                                (int)$template->getStartTime()->format('i')
                            );
                            
                            $endTime = clone $current;
                            $endTime->setTime(
                                (int)$template->getEndTime()->format('H'),
                                (int)$template->getEndTime()->format('i')
                            );

                            $shift->setStartTime($startTime);
                            $shift->setEndTime($endTime);
                            $shift->setStatus('scheduled'); // 自動生成は確定扱いとするか、'draft'ステータスを作るか。今回はscheduledで。

                            $this->entityManager->persist($shift);
                            $generatedCount++;
                            $assignedCount++;
                        }
                    }
                }
            }
            $current->modify('+1 day');
        }

        $this->entityManager->flush();
        return $generatedCount;
    }
}
