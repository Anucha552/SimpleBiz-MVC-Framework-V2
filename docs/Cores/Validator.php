Validator.md
ภาพรวม

Validator คือคลาสสำหรับตรวจสอบข้อมูล (Data Validation) ภายในระบบ Framework
ออกแบบให้ใช้งานง่าย รองรับ:

Multiple rules ต่อ 1 field

Custom message

Label สำหรับแสดงผล error

Rule แบบมี parameter เช่น min:3, between:1,10

Rule ที่เชื่อมกับ Database เช่น unique, exists

Result caching (ไม่ validate ซ้ำโดยไม่จำเป็น)

แนวคิดสำคัญ:

หนึ่ง field สามารถมีหลาย rule และระบบจะหยุดเมื่อเจอ rule ผิด หากใช้ bail

การสร้าง Validator
use App\Core\Validator;

$validator = new Validator($data, $rules);
ตัวอย่าง
$data = [
    'email' => 'test@example.com',
    'age'   => 18,
];

$rules = [
    'email' => 'required|email',
    'age'   => 'required|numeric|min:18'
];

$validator = new Validator($data, $rules);
การตรวจสอบข้อมูล
ตรวจว่าผ่านหรือไม่
if ($validator->passes()) {
    echo "Validation Passed";
}
ตรวจว่าล้มเหลวหรือไม่
if ($validator->fails()) {
    print_r($validator->errors());
}

ระบบจะ cache ผลลัพธ์ไว้
เรียก passes() หรือ fails() ซ้ำจะไม่ validate ใหม่

การดึงข้อมูลที่ผ่าน validation แล้ว
$validated = $validator->validated();

จะคืนค่าเฉพาะ field ที่ผ่าน rule เท่านั้น

การกำหนดหลาย Rule

ใช้เครื่องหมาย | คั่น

'username' => 'required|min:3|max:20'
การใช้ bail

bail จะหยุดตรวจ rule ถัดไปทันทีเมื่อเจอ error

'username' => 'bail|required|min:3|max:20'

เหมาะกับกรณีที่ไม่ต้องการให้ error ซ้อนหลายข้อความ

การตั้งค่า Label

ช่วยให้ error message อ่านง่ายขึ้น

$validator->setLabels([
    'email' => 'Email Address',
    'age'   => 'Age'
]);

ถ้าไม่กำหนด ระบบจะใช้ชื่อ field แทน

การตั้งค่า Custom Message
$validator->setMessages([
    'email.required' => 'กรุณากรอกอีเมล',
    'age.min'        => 'อายุต้องไม่น้อยกว่า 18 ปี'
]);

รูปแบบ key:

field.rule
ตัวอย่าง Rule ที่รองรับ
required
'name' => 'required'
email
'email' => 'required|email'
numeric
'age' => 'numeric'
min
'age' => 'min:18'
max
'username' => 'max:20'
between
'age' => 'between:18,60'
match (ยืนยันค่าเหมือน field อื่น)
'password_confirmation' => 'match:password'
unique (เช็คค่าซ้ำในฐานข้อมูล)
'email' => 'unique:users,email'

รูปแบบ:

unique:table,column

ต้องมี Database instance ส่งเข้า Validator

exists (ตรวจสอบว่ามีข้อมูลอยู่จริง)
'user_id' => 'exists:users,id'

รูปแบบ:

exists:table,column
ตัวอย่างใช้งานร่วมกับ Controller
public function store()
{
    $data = $_POST;

    $validator = new Validator($data, [
        'name'  => 'required|min:3',
        'email' => 'required|email|unique:users,email',
    ]);

    if ($validator->fails()) {
        return $this->json([
            'errors' => $validator->errors()
        ], 422);
    }

    $validated = $validator->validated();

    User::create($validated);

    return $this->json([
        'message' => 'Created successfully'
    ]);
}
พฤติกรรมสำคัญภายในระบบ

Validation จะทำงานครั้งเดียว (มี result cache)

validated() จะ auto-run validate หากยังไม่เคยรัน

Rule ที่ไม่รู้จักจะ throw exception

ป้องกัน SQL Injection ใน rule database

"0" จะไม่ถูกมองว่าเป็น empty

Rule ที่มี parameter จะ normalize ค่าอัตโนมัติ

Best Practice

ใช้ bail กับ field ที่มีหลาย rule

แยก validation ออกจาก business logic

ใช้ custom message ใน production

ใช้ label เพื่อให้ error อ่านง่าย

อย่า validate ใน Model — ทำใน Controller หรือ Request Layer

สรุปแนวคิดเชิงสถาปัตยกรรม

Validator นี้ออกแบบให้:

Stateless หลัง validate

Predictable behavior

Fail fast เมื่อ rule ผิดพลาด

พร้อมรองรับการขยายเป็น Rule Object Pattern ในอนาคต