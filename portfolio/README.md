# 🌐 Personal Web Developer Portfolio

เว็บไซต์พอร์ตโฟลิโอส่วนตัว (One-Page Portfolio) ออกแบบด้วยสไตล์ Modern Dark Mode + Glassmorphism เรียบหรู โหลดไว ไม่พึ่งพา Framework ใหญ่

---

## 📁 โครงสร้างโปรเจกต์ (File Structure)

```text
portfolio/
├── index.html       # โครงสร้างหน้าเว็บหลัก (Semantic HTML5)
├── style.css        # สไตล์และลูกเล่น Glassmorphism / Dark Mode
├── app.js           # ระบบ Interactive (Smooth Scroll, Active Navbar, Copy Email, Skill Bars)
└── README.md        # คู่มือการปรับแต่งและวิธีนำขึ้นออนไลน์
```

---

## 🚀 วิธีเปิดดูบนเครื่อง (Local Run)

1. เปิด XAMPP แล้วกดปุ่ม **Start** ที่ **Apache**
2. เข้าชมผ่านเว็บเบราว์เซอร์ได้ที่ลิงก์:
   ```text
   http://localhost/lab_month_korn/portfolio/
   ```

---

## 🌍 วิธีนำขึ้นโฮสต์ฟรีบน GitHub Pages (ทำใน 3 นาที)

หากต้องการให้มีเว็บไซต์ออนไลน์เป็นของตัวเอง (เช่น `https://kornza007.github.io`) ฟรี 100% โดยไม่ต้องเช่าโฮสติ้ง:

### วิธีที่ 1: สร้าง Repository แยกเฉพาะสำหรับเว็บพอร์ต (แนะนำที่สุด ⭐)
1. ไปที่ [GitHub](https://github.com/) แล้วกดสร้าง **New Repository** ใหม่
   * ตั้งชื่อ Repository ว่า `portfolio` หรือ `Kornza007.github.io`
   * เลือกเป็น **Public**
2. คัดลอกไฟล์ทั้ง 3 ไฟล์ (`index.html`, `style.css`, `app.js`) จากโฟลเดอร์ `portfolio/` นี้ไปใส่ไว้ใน Repository ใหม่
3. ไปที่แท็บ **Settings** ของ Repository นั้น $\rightarrow$ เมนูด้านซ้ายเลือก **Pages**
4. ตรงส่วน **Branch** ให้เลือกเป็น `main` และโฟลเดอร์ `/ (root)` แล้วกด **Save**
5. รอ 1–2 นาที จะได้รับลิงก์เว็บพอร์ตโฟลิโอออนไลน์ทันที! (เช่น `https://kornza007.github.io/portfolio/`)

---

## ✏️ จุดที่สามารถปรับแต่งข้อมูลตัวเองได้ (Customization)

เปิดไฟล์ `index.html` แล้วค้นหาเพื่อเปลี่ยนข้อมูล:
* **ชื่อและรูปภาพ:** ค้นหา `ศิวกร (Korn)` และ `avatar-img`
* **อีเมลและเบอร์ติดต่อ:** ค้นหา `sivakorn.tech@gmail.com` เพื่อเปลี่ยนเป็นอีเมลส่วนตัวจริงของคุณ
* **ลิงก์ Social Media:** ค้นหา `https://github.com/Kornza007` เพื่อใส่ลิงก์ GitHub หรือ LinkedIn ของคุณ
* **ระดับทักษะ (Skill %):** ค้นหา `data-width="90%"` เพื่อปรับเปอร์เซ็นต์หลอดพลังของแต่ละทักษะ
