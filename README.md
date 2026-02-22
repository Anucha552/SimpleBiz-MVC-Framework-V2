# SimpleBiz MVC Framework V2

**เฟรมเวิร์ก MVC ขนาดเล็ก-กลาง สำหรับพัฒนาเว็บแอปและ API แบบปลอดภัย และขยายได้ง่าย**

## Controller convenience wrappers

Short examples showing how to use the new base controller helpers:

Render a view:

```php
// inside a controller action
$this->view('home', ['name' => 'Alice']);
```

Validate and redirect on failure:

```php
$response = $this->validateOrRedirect($this->all(), [
	'email' => 'required|email',
	'password' => 'required|min:6'
]);

if ($response) {
	return $response; // redirect with errors + old input
}
```

Return JSON success:

```php
return $this->jsonSuccess(['id' => $user->id], 'User created');
```

Flash + redirect:

```php
$this->flash('success', 'Profile updated');
return $this->redirect('/profile');
```

Share data with all views:

```php
$this->share('appName', 'My App');
```

