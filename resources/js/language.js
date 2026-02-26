import { ref, computed } from 'vue';

// สถานะภาษาปัจจุบัน (Global State)
const currentLanguage = ref(localStorage.getItem('fitpung_lang') || 'TH');

// ชุดข้อมูลภาษา (รวมไว้ที่เดียว)
const translations = {
    TH: {
        common: {
            save: 'บันทึก',
            cancel: 'ยกเลิก',
            back: 'กลับ',
            active: 'เปิดใช้งาน',
            present: 'ปัจจุบัน',
            loading: 'กำลังโหลด...',
            edit: 'แก้ไข',
            delete: 'ลบ',
            today: 'วันนี้',
            kg: 'กก.',
            min: 'นาที',
            kcal: 'แคลลอรี่',
        },
        dashboard: {
            title: 'จัดการยิม',
            gym_layouts: 'เลย์เอาต์ยิมของคุณ',
            create_new: 'สร้างยิมใหม่',
            no_layouts: 'ยังไม่มีเลย์เอาต์',
            view_map: 'ดูแผนที่',
            edit_gym: 'แก้ไขยิม',
        },
        profile: {
            title: 'โปรไฟล์บัญชี',
            edit: 'แก้ไขโปรไฟล์',
            weight: 'น้ำหนัก',
            height: 'ส่วนสูง',
            goal: 'เป้าหมาย',
            history: 'ประวัติน้ำหนัก',
            weight_trends: 'แนวโน้มน้ำหนัก',
            settings: 'ตั้งค่า',
            logout: 'ออกจากระบบ',
        },
        maps: {
            title: 'ค้นหายิม',
            search_placeholder: 'ค้นหาชื่อยิม...',
            all_gyms: 'ยิมทั้งหมด',
            favorites: 'รายการโปรด',
            no_gyms: 'ไม่พบข้อมูลยิม',
            select_gym: 'เลือกยิมนี้',
        },
        home: {
            elite: 'ฟิตปัง',
            stay_focused: 'เริ่มออกกำลัง\nให้ตรงจุด',
            hero_subtitle: 'ผลักดันขีดจำกัดของคุณวันนี้ด้วยแผนส่วนบุคคล',
            sets_done: 'เซ็ตที่ทำได้',
            workouts_done: 'ออกกำลังไปแล้ว',
            daily_feed: 'ฟีดรายวัน',
            for_you: 'สำหรับคุณ',
            start_guided: 'เริ่มโปรแกรมแนะนำ',
        },
        stats: {
            title: 'ประสิทธิภาพ',
            week: 'สัปดาห์',
            month: 'เดือน',
            year: 'ปี',
            workout_days: 'วันที่มาเล่น',
            total_sets: 'เซ็ตทั้งหมด',
            active_time: 'เวลาที่ใช้',
            consistency: 'ความสม่ำเสมอ',
            daily_split: 'ส่วนที่เล่นรายวัน',
            list: 'รายการ',
            calendar: 'ปฏิทิน',
            no_workout: 'ไม่มีการออกกำลังกาย',
            details: 'รายละเอียดการออกกำลังกาย',
            sets: 'เซ็ต',
            reps: 'ครั้ง',
            weight: 'น้ำหนัก',
            today: 'วันนี้',
            avg: 'เฉลี่ย',
            wk: 'สัปดาห์',
        },
        workout: {
            choose_mode: 'เลือกโหมดการเล่น',
            select_train: 'เลือกรูปแบบการฝึกซ้อมของคุณ',
            manual: 'เลือกเครื่องเล่นเอง',
            search_exercises: 'ค้นหาท่าออกกำลังกาย',
            interactive: 'โหมดยิมแนะนำ',
            gym_workout: 'ยิม เวิร์คเอาท์',
            gym_subtitle: 'เลือกยิม ใช้แผนที่ และทำตามโปรแกรมของเจ้าของยิม',
            free_workout: 'ฟรี เวิร์คเอาท์',
            free_subtitle: 'เลือกท่าออกกำลังกายด้วยตัวเองและบันทึกเซ็ตของคุณ',
            start: 'เริ่ม',
            finish: 'จบเซสชั่น',
            equipment_pool: 'คลังอุปกรณ์',
            my_plans: 'แผนของฉัน',
            history: 'ประวัติการเล่น',
            build_session: 'สร้างเซสชั่นของคุณ',
            items_selected: 'รายการที่เลือก',
            no_saved_plans: 'ยังไม่มีแผนที่บันทึกไว้',
            no_results: 'ไม่พบข้อมูล',
        },
        settings: {
            title: 'การตั้งค่า',
            appearance: 'รูปลักษณ์',
            dark_mode: 'โหมดมืด',
            language: 'ภาษา',
            notifications: 'การแจ้งเตือน',
            version: 'เวอร์ชัน',
        },
        nav: {
            home: 'หน้าแรก',
            maps: 'แผนที่',
            workout: 'เริ่มออกกำลังกาย',
            stats: 'สถิติ',
            profile: 'โปรไฟล์',
        }
    },
    EN: {
        common: {
            save: 'Save',
            cancel: 'Cancel',
            back: 'Back',
            active: 'Active',
            present: 'Present',
            loading: 'Loading...',
            edit: 'Edit',
            delete: 'Delete',
            today: 'Today',
            kg: 'kg',
            min: 'min',
            kcal: 'kcal',
        },
        dashboard: {
            title: 'Gym Management',
            gym_layouts: 'Your Gym Layouts',
            create_new: 'Create New Gym',
            no_layouts: 'No layouts found',
            view_map: 'View Map',
            edit_gym: 'Edit Gym',
        },
        profile: {
            title: 'Account Profile',
            edit: 'Edit Profile',
            weight: 'Weight',
            height: 'Height',
            goal: 'Goal',
            history: 'Weight History',
            weight_trends: 'Weight Trends',
            settings: 'Settings',
            logout: 'Logout',
        },
        maps: {
            title: 'Search Gym',
            search_placeholder: 'Search gym name...',
            all_gyms: 'All Gyms',
            favorites: 'Favorites',
            no_gyms: 'No gyms found',
            select_gym: 'Select Gym',
        },
        home: {
            elite: 'FitPung',
            stay_focused: 'TRAIN\nSMARTER.',
            hero_subtitle: 'Push your limits today with your personalized plan.',
            sets_done: 'Sets Done',
            workouts_done: 'Workouts Done',
            daily_feed: 'Daily Feed',
            for_you: 'For You',
            start_guided: 'Start Guided Workout',
        },
        stats: {
            title: 'Performance',
            week: 'Week',
            month: 'Month',
            year: 'Year',
            workout_days: 'Workout Days',
            total_sets: 'Total Sets',
            active_time: 'Active Time',
            consistency: 'Consistency',
            daily_split: 'Daily Split',
            list: 'List',
            calendar: 'Calendar',
            no_workout: 'No Workout',
            details: 'Workout Details',
            sets: 'Sets',
            reps: 'Reps',
            weight: 'Weight',
            today: 'Today',
            avg: 'Avg',
            wk: 'wk',
        },
        workout: {
            choose_mode: 'Choose Mode',
            select_train: 'Select your training style',
            manual: 'Manual Selection',
            search_exercises: 'Search Exercises',
            interactive: 'Interactive Mode',
            gym_workout: 'Gym Workout',
            gym_subtitle: 'Select a gym, use the map, and follow owner-set programs.',
            free_workout: 'Free Workout',
            free_subtitle: 'Choose exercises manually and track your own sets.',
            start: 'Start',
            finish: 'Finish Session',
            equipment_pool: 'Equipment Pool',
            my_plans: 'My Plans',
            history: 'History',
            build_session: 'Build Your Session',
            items_selected: 'Items Selected',
            no_saved_plans: 'No saved plans yet',
            no_results: 'No results found',
        },
        settings: {
            title: 'Settings',
            appearance: 'Appearance',
            dark_mode: 'Dark Mode',
            language: 'Language',
            notifications: 'Notifications',
            version: 'Version',
        },
        nav: {
            home: 'Home',
            maps: 'Maps',
            workout: 'Workout',
            stats: 'Stats',
            profile: 'Profile',
        }
    }
};

export function useI18n() {
    // ฟังก์ชันดึงคำแปล
    const t = (path) => {
        const keys = path.split('.');
        let result = translations[currentLanguage.value];

        for (const key of keys) {
            if (result && result[key]) {
                result = result[key];
            } else {
                return path; // คืนค่า path ถ้าหากไม่เจอคำแปล
            }
        }
        return result;
    };

    // ฟังก์ชันสลับภาษา
    const toggleLanguage = () => {
        currentLanguage.value = currentLanguage.value === 'TH' ? 'EN' : 'TH';
        localStorage.setItem('fitpung_lang', currentLanguage.value);
    };

    return {
        t,
        currentLanguage,
        toggleLanguage
    };
}
