<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Equipment;

class EquipmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing equipment
        Equipment::truncate();

        $equipment = [
            [
                "name" => "Bench Press Machine",
                "filename" => "BenchPress.svg",
                "type" => "Strength",
                "target_muscles" => [
                    ["key" => "pectorals", "name_th" => "หน้าอก (Chest)"],
                    ["key" => "triceps", "name_th" => "หลังแขน (Triceps)"],
                    ["key" => "anterior_deltoids", "name_th" => "ไหล่ด้านหน้า (Front Delts)"]
                ],
                "technique" => [
                    "pectorals" => [
                        "variation_name" => "Chest Press Focus",
                        "setup" => ["title" => "การจัดท่า", "description" => "นั่งหลังพิงเบาะให้แน่น เกร็งหน้าท้อง มือจับด้ามจับในระดับอกบน"],
                        "bar_position" => ["title" => "การเคลื่อนไหว", "description" => "ดันออกจากตัวจนสุดแขนแต่ไม่ล็อกข้อศอก แล้วผ่อนกลับช้าๆ"],
                        "elbow_angle" => ["title" => "มุมข้อศอก", "description" => "วางศอกทำมุมประมาณ 45-60 องศากับลำตัว", "warning" => "ไม่ควรกางศอกสูงเกินไปเพื่อลดการบาดเจ็บไหล่"],
                        "breathing" => ["title" => "การหายใจ", "description" => "หายใจออกตอนดัน หายใจเข้าตอนผ่อน"]
                    ]
                ]
            ],
            [
                "name" => "Lat Pulldown Machine",
                "filename" => "LatPulldown.svg",
                "type" => "Strength",
                "target_muscles" => [
                    ["key" => "latissimus_dorsi", "name_th" => "กล้ามเนื้อปีก (Lat)"],
                    ["key" => "biceps", "name_th" => "หน้าแขน (Biceps)"],
                    ["key" => "traps", "name_th" => "หลังส่วนบน (Upper Back)"]
                ],
                "technique" => [
                    "latissimus_dorsi" => [
                        "variation_name" => "Standard Lat Pulldown",
                        "setup" => ["title" => "การจัดท่า", "description" => "ปรับเบาะล็อกขาให้แน่น จับบาร์กว้างกว่าไหล่เล็กน้อย แอ่นอกขึ้น"],
                        "bar_position" => ["title" => "การเคลื่อนไหว", "description" => "ดึงบาร์ลงมาที่ระดับอกส่วนบน โฟกัสการบีบสะบักเข้าหากัน"],
                        "elbow_angle" => ["title" => "ตำแหน่งศอก", "description" => "ดึงศอกลงหาพื้นและไปทางด้านข้างลำตัว", "warning" => "อย่าห่อไหล่หรือโน้มตัวไปข้างหลังมากเกินไป"],
                        "breathing" => ["title" => "การหายใจ", "description" => "หายใจออกตอนดึงลง หายใจเข้าตอนปล่อยบาร์ขึ้น"]
                    ],
                    "biceps" => [
                        "variation_name" => "Reverse Grip Pulldown",
                        "setup" => ["title" => "การจัดท่า", "description" => "หงายมือจับบาร์ความกว้างเท่าไหล่"],
                        "bar_position" => ["title" => "การเคลื่อนไหว", "description" => "ดึงบาร์ลงมาหาอก โฟกัสแรงที่หน้าแขน"],
                        "elbow_angle" => ["title" => "มุมศอก", "description" => "หนีบศอกให้ชิดลำตัวตลอดเวลา", "warning" => "ระวังอย่าเหวี่ยงตัวช่วยดึง"],
                        "breathing" => ["title" => "การหายใจ", "description" => "หายใจออกตอนดึง หายใจเข้าตอนผ่อน"]
                    ]
                ]
            ],
            [
                "name" => "Dumbbell Set (Full Range)",
                "filename" => "Dumbbells.svg",
                "type" => "Strength",
                "target_muscles" => [
                    ["key" => "biceps", "name_th" => "หน้าแขน (Biceps)"],
                    ["key" => "anterior_deltoids", "name_th" => "หัวไหล่ (Shoulders)"],
                    ["key" => "triceps", "name_th" => "หลังแขน (Triceps)"],
                    ["key" => "forearms", "name_th" => "แขนท่อนล่าง (Forearms)"]
                ],
                "technique" => [
                    "biceps" => [
                        "variation_name" => "Dumbbell Bicep Curl",
                        "setup" => ["title" => "การจัดท่า", "description" => "ยืนตัวตรง ปล่อยแขนข้างลำตัว หงายฝ่ามือออก"],
                        "bar_position" => ["title" => "การเคลื่อนไหว", "description" => "เกร็งหน้าแขน ดึงดัมเบลขึ้นหาไหล่โดยไม่เหวี่ยงตัว"],
                        "elbow_angle" => ["title" => "ตำแหน่งศอก", "description" => "ล็อกศอกให้ติดข้างลำตัวตลอดการเคลื่อนที่", "warning" => "อย่าใช้แรงจากหลังส่วนล่างในการช่วยยก"],
                        "breathing" => ["title" => "การหายใจ", "description" => "หายใจออกตอนยกขึ้น หายใจเข้าตอนปล่อยลง"]
                    ],
                    "anterior_deltoids" => [
                        "variation_name" => "Lateral Raise",
                        "setup" => ["title" => "การจัดท่า", "description" => "ยืนแยกเท้าเท่าช่วงไหล่ ถือดัมเบลไว้ข้างลำตัว"],
                        "bar_position" => ["title" => "การเคลื่อนไหว", "description" => "กางแขนออกด้านข้างจนขนานกับพื้น"],
                        "elbow_angle" => ["title" => "มุมแขน", "description" => "งอข้อศอกเล็กน้อยขณะกางแขนออก", "warning" => "อย่าโยกตัวเพื่อช่วยแรงเหวี่ยง"],
                        "breathing" => ["title" => "การหายใจ", "description" => "หายใจออกตอนกางแขน หายใจเข้าตอนหุบแขนลง"]
                    ]
                ]
            ],
            [
                "name" => "Smith Machine Multi-Rack",
                "filename" => "SmithMachine.svg",
                "type" => "Strength",
                "target_muscles" => [
                    ["key" => "anterior_deltoids", "name_th" => "ไหล่ (Shoulders)"],
                    ["key" => "pectorals", "name_th" => "หน้าอก (Chest)"],
                    ["key" => "quadriceps", "name_th" => "ขา (Legs)"]
                ],
                "technique" => [
                    "anterior_deltoids" => [
                        "variation_name" => "Overhead Shoulder Press",
                        "setup" => ["title" => "การจัดท่า", "description" => "ตั้งม้านั่ง 90 องศา นั่งหลังพิงเบาะ บาร์อยู่ระดับคาง"],
                        "bar_position" => ["title" => "การเคลื่อนไหว", "description" => "ดันบาร์ขึ้นตรงๆ จนสุดแขน แล้วผ่อนลงมาช้าๆ"],
                        "elbow_angle" => ["title" => "มุมศอก", "description" => "กางศอกออกด้านข้างเล็กน้อย", "warning" => "ระวังอย่าแอ่นหลังมากเกินไปขณะดันขึ้น"],
                        "breathing" => ["title" => "การหายใจ", "description" => "หายใจออกตอนดันขึ้น หายใจเข้าตอนลดบาร์ลง"]
                    ],
                    "quadriceps" => [
                        "variation_name" => "Smith Machine Squat",
                        "setup" => ["title" => "การจัดท่า", "description" => "แกว่งเท้ามาข้างหน้าบาร์เล็กน้อย พิงหลังกับบาร์"],
                        "bar_position" => ["title" => "การเคลื่อนไหว", "description" => "ย่อตัวลงจนต้นขาขนานกับพื้น แล้วดันตัวกลับขึ้นมา"],
                        "elbow_angle" => ["title" => "มุมเข่า", "description" => "รักษาทิศทางเข่าให้ตรงกับปลายเท้า", "warning" => "ห้ามลุกขึ้นโดยทิ้งน้ำหนักลงที่ปลายเท้าข้างเดียว"],
                        "breathing" => ["title" => "การหายใจ", "description" => "หายใจเข้าตอนลง หายใจออกตอนขึ้น"]
                    ]
                ]
            ],
            [
                "name" => "Elliptical Cross Trainer",
                "filename" => "Elliptical.svg",
                "type" => "Cardio",
                "target_muscles" => [
                    ["key" => "cardio", "name_th" => "หัวใจและปอด (Cardio)"],
                    ["key" => "quadriceps", "name_th" => "ขาด้านหน้า"],
                    ["key" => "glutes", "name_th" => "ก้น"]
                ],
                "technique" => [
                    "cardio" => [
                        "variation_name" => "Full Body Cardio",
                        "setup" => ["title" => "ท่าทาง", "description" => "ยืนตัวตรง จับที่ด้ามจับแบบเคลื่อนที่เพื่อฝึกแขนและขาพร้อมกัน"],
                        "bar_position" => ["title" => "จังหวะ", "description" => "ก้าวขาเป็นวงรีอย่างต่อเนื่อง สม่ำเสมอ"],
                        "elbow_angle" => ["title" => "โฟกัส", "description" => "พยายามกดส้นเท้าลงเพื่อกระตุ้นกล้ามเนื้อก้นและป้องกันอาการชา"],
                        "breathing" => ["title" => "การหายใจ", "description" => "หายใจเข้า-ออกลึกๆ สม่ำเสมอตามจังหวะก้าว"]
                    ]
                ]
            ],
            [
                "name" => "Recumbent Bike",
                "filename" => "RecumbentCycle.svg",
                "type" => "Cardio",
                "target_muscles" => [
                    ["key" => "cardio", "name_th" => "หัวใจและปอด (Cardio)"],
                    ["key" => "quadriceps", "name_th" => "ต้นขา (Quads)"]
                ],
                "technique" => [
                    "cardio" => [
                        "variation_name" => "Low Impact Cycling",
                        "setup" => ["title" => "การจัดท่า", "description" => "ปรับระยะเบาะให้เข่างอเล็กน้อยเมื่อขาถีบออกไปไกลที่สุด"],
                        "bar_position" => ["title" => "จังหวะ", "description" => "ปั่นด้วยรอบขาคงที่ (RPM 60-80)"],
                        "elbow_angle" => ["title" => "ท่าทาง", "description" => "เอนหลังพิงเบาะ ผ่อนคลายไหล่และมือจับด้านข้าง", "warning" => "ไม่ควรปรับเบาะไกลเกินไปจนต้องเอื้อมเท้า"],
                        "breathing" => ["title" => "การหายใจ", "description" => "หายใจปกติ ไม่กลั้นหายใจ"]
                    ]
                ]
            ],
            [
                "name" => "Professional Treadmill",
                "filename" => "Treadmill.svg",
                "type" => "Cardio",
                "target_muscles" => [
                    ["key" => "cardio", "name_th" => "หัวใจและปอด (Cardio)"],
                    ["key" => "calves", "name_th" => "น่อง (Calves)"],
                    ["key" => "quadriceps", "name_th" => "ขา (Legs)"]
                ],
                "technique" => [
                    "cardio" => [
                        "variation_name" => "Running / Interval",
                        "setup" => ["title" => "ท่าทาง", "description" => "มองตรงไปข้างหน้า แกว่งแขนตามธรรมชาติ ไม่ก้มหน้ามองเท้า"],
                        "bar_position" => ["title" => "การลงเท้า", "description" => "ลงน้ำหนักที่กลางฝ่าเท้าหรือค่อนไปทางหน้าเท้าเพื่อลดแรงกระแทก"],
                        "elbow_angle" => ["title" => "ข้อควรระวัง", "description" => "อย่าจับราวขณะวิ่งเพราะจะทำให้เผาผลาญลดลงและเสียสมดุล"],
                        "breathing" => ["title" => "การหายใจ", "description" => "หายใจเข้าทางจมูก หายใจออกทางปาก"]
                    ],
                    "calves" => [
                        "variation_name" => "Incline Power Walk",
                        "setup" => ["title" => "การจัดท่า", "description" => "ปรับความชัน (Incline) 5-10% เพื่อเน้นน่องและก้น"],
                        "bar_position" => ["title" => "จังหวะ", "description" => "เดินเร็วอย่างมั่นคง ก้าวเท้าให้ยาวขึ้นเล็กน้อย"],
                        "elbow_angle" => ["title" => "ท่าทาง", "description" => "โน้มตัวไปข้างหน้าเล็กน้อยตามความชัน"],
                        "breathing" => ["title" => "การหายใจ", "description" => "หายใจสม่ำเสมอสอดคล้องกับจังหวะเดิน"]
                    ]
                ]
            ]
        ];

        foreach ($equipment as $item) {
            Equipment::create($item);
        }
    }
}
