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
                "name" => "Bench Press",
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
                "name" => "Elliptical",
                "filename" => "Elliptical.svg",
                "type" => "Cardio",
                "target_muscles" => [
                    ["key" => "cardio", "name_th" => "หัวใจและปอด (Cardio)"],
                    ["key" => "biceps", "name_th" => "หน้าแขน (Biceps)"],
                    ["key" => "triceps", "name_th" => "หลังแขน (Triceps)"],
                    ["key" => "anterior_deltoids", "name_th" => "ไหล่ (Shoulders)"],
                    ["key" => "glutes", "name_th" => "ก้น (Glutes)"]
                ],
                "technique" => [
                    "cardio" => [
                        "variation_name" => "คาดิโอทั่วร่าง",
                        "setup" => ["title" => "ท่าทาง", "description" => "ยืนตัวตรง จับที่ด้ามจับแบบเคลื่อนที่เพื่อฝึกแขนและขาพร้อมกัน"],
                        "bar_position" => ["title" => "จังหวะ", "description" => "ก้าวขาเป็นวงรีอย่างต่อเนื่อง สม่ำเสมอ"],
                        "elbow_angle" => ["title" => "โฟกัส", "description" => "พยายามกดส้นเท้าลงเพื่อกระตุ้นกล้ามเนื้อก้นและป้องกันอาการชา"],
                        "breathing" => ["title" => "การหายใจ", "description" => "หายใจเข้า-ออกลึกๆ สม่ำเสมอตามจังหวะก้าว"]
                    ]
                ]
            ],

            [
                "name" => "Dumbbells",
                "filename" => "Dumbbells.svg",
                "type" => "Strength",
                "target_muscles" => [
                    ["key" => "biceps", "name_th" => "หน้าแขน (Biceps)"],
                    ["key" => "anterior_deltoids", "name_th" => "หัวไหล่ (Shoulders)"],
                    ["key" => "triceps", "name_th" => "หลังแขน (Triceps)"],
                    ["key" => "traps", "name_th" => "บ่า (Traps)"],
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
                "name" => "Smith Machine",
                "filename" => "SmithMachine.svg",
                "type" => "Strength",
                "target_muscles" => [
                    ["key" => "anterior_deltoids", "name_th" => "ไหล่ (Shoulders)"],
                    ["key" => "pectorals", "name_th" => "หน้าอก (Chest)"],
                    ["key" => "traps", "name_th" => "บ่า (Traps)"]
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
                "name" => "Incline Bench Press",
                "filename" => "DeclineBenchPress.svg",
                "type" => "Strength",
                "target_muscles" => [
                    ["key" => "upper_pectorals", "name_th" => "หน้าอกส่วนบน (Upper Chest)"],
                    ["key" => "triceps", "name_th" => "หลังแขน (Triceps)"],
                    ["key" => "biceps", "name_th" => "หน้าแขน (Biceps)"],
                    ["key" => "anterior_deltoids", "name_th" => "ไหล่ด้านหน้า (Front Delts)"],
                    ["key" => "forearms", "name_th" => "แขนท่อนล่าง (Forearms)"]
                ],
                "technique" => [
                    "upper_pectorals" => [
                        "variation_name" => "Incline Press Focus",
                        "setup" => ["title" => "การจัดท่า", "description" => "นอนบนม้านั่งแบบลาดชันขึ้น จับบาร์กว้างกว่าแขนเล็กน้อย"],
                        "bar_position" => ["title" => "การเคลื่อนไหว", "description" => "ลดบาร์ลงมาที่ระดับอกส่วนบน แล้วดันขึ้นจนสุดแขน"],
                        "elbow_angle" => ["title" => "มุมศอก", "description" => "รักษาแนวศอกให้อยู่ใต้บาร์และไม่กางกว้างเกินไป"],
                        "breathing" => ["title" => "การหายใจ", "description" => "หายใจเข้าตอนผ่อน หายใจออกตอนดัน"]
                    ]
                ]
            ],
            [
                "name" => "Leg Press",
                "filename" => "LegPress.svg",
                "type" => "Strength",
                "target_muscles" => [
                    ["key" => "quadriceps", "name_th" => "หน้าขา (Quadriceps)"],
                    ["key" => "glutes", "name_th" => "ก้น (Glutes)"],
                    ["key" => "hamstrings", "name_th" => "หลังขา (Hamstrings)"]
                ],
                "technique" => [
                    "quadriceps" => [
                        "variation_name" => "Standard Leg Press",
                        "setup" => ["title" => "การจัดท่า", "description" => "นั่งพิงเบาะให้แน่น วางเท้ากว้างเท่าช่วงไหล่บนแผ่นเหยียบ"],
                        "bar_position" => ["title" => "การเคลื่อนไหว", "description" => "ปลดล็อกเซฟตี้ ลดแผ่นเหยียบลงจนเข่าทำมุม 90 องศา แล้วดันกลับขึ้นไป"],
                        "elbow_angle" => ["title" => "ตำแหน่งเข่า", "description" => "ไม่ล็อกหัวเข่าเมื่อดันขึ้นสุด และระวังอย่าให้เข่าหุบเข้าหากัน"],
                        "breathing" => ["title" => "การหายใจ", "description" => "หายใจเข้าตอนผ่อน หายใจออกตอนดัน"]
                    ]
                ]
            ],
            [
                "name" => "Treadmill",
                "filename" => "Treadmill.svg",
                "type" => "Cardio",
                "target_muscles" => [
                    ["key" => "cardio", "name_th" => "หัวใจและปอด (Cardio)"],
                    ["key" => "calves", "name_th" => "น่อง (Calves)"],
                    ["key" => "quadriceps", "name_th" => "หน้าขา (Quadriceps)"]
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
