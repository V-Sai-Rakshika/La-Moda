<?php
/**
 * validates.php — Backend form validation (La Moda)
 * Include in any PHP that handles POST data.
 *
 * Usage:
 *   include 'validates.php';
 *   $errors = LaModa\validate($_POST, ['mobile' => 'phone', 'pincode' => 'pincode', ...]);
 */

namespace LaModa;

// Valid Indian states (lowercase for comparison)
const INDIA_STATES = [
    'andhra pradesh','arunachal pradesh','assam','bihar','chhattisgarh',
    'goa','gujarat','haryana','himachal pradesh','jharkhand','karnataka',
    'kerala','madhya pradesh','maharashtra','manipur','meghalaya','mizoram',
    'nagaland','odisha','punjab','rajasthan','sikkim','tamil nadu','telangana',
    'tripura','uttar pradesh','uttarakhand','west bengal',
    'andaman and nicobar islands','chandigarh',
    'dadra and nagar haveli and daman and diu',
    'delhi','jammu and kashmir','ladakh','lakshadweep','puducherry',
];

// Pincode → state (first 2 digits)
const PINCODE_STATE = [
    '11'=>'Delhi',
    '12'=>'Haryana','13'=>'Haryana',
    '14'=>'Punjab','15'=>'Punjab','16'=>'Punjab',
    '17'=>'Himachal Pradesh',
    '18'=>'Jammu and Kashmir','19'=>'Jammu and Kashmir',
    '20'=>'Uttar Pradesh','21'=>'Uttar Pradesh','22'=>'Uttar Pradesh',
    '23'=>'Uttar Pradesh','24'=>'Uttar Pradesh','25'=>'Uttar Pradesh',
    '26'=>'Uttar Pradesh','27'=>'Uttar Pradesh','28'=>'Uttar Pradesh',
    '30'=>'Rajasthan','31'=>'Rajasthan','32'=>'Rajasthan',
    '33'=>'Rajasthan','34'=>'Rajasthan',
    '36'=>'Gujarat','37'=>'Gujarat','38'=>'Gujarat','39'=>'Gujarat',
    '40'=>'Maharashtra','41'=>'Maharashtra','42'=>'Maharashtra',
    '43'=>'Maharashtra','44'=>'Maharashtra',
    '45'=>'Madhya Pradesh','46'=>'Madhya Pradesh',
    '47'=>'Madhya Pradesh','48'=>'Madhya Pradesh',
    '49'=>'Chhattisgarh',
    '50'=>'Telangana',
    '51'=>'Andhra Pradesh','52'=>'Andhra Pradesh','53'=>'Andhra Pradesh',
    '56'=>'Karnataka','57'=>'Karnataka','58'=>'Karnataka','59'=>'Karnataka',
    '60'=>'Tamil Nadu','61'=>'Tamil Nadu','62'=>'Tamil Nadu',
    '63'=>'Tamil Nadu','64'=>'Tamil Nadu',
    '67'=>'Kerala','68'=>'Kerala','69'=>'Kerala',
    '70'=>'West Bengal','71'=>'West Bengal','72'=>'West Bengal',
    '73'=>'West Bengal','74'=>'West Bengal',
    '75'=>'Odisha','76'=>'Odisha','77'=>'Odisha',
    '78'=>'Assam','79'=>'Arunachal Pradesh',
    '80'=>'Bihar','81'=>'Bihar','82'=>'Jharkhand',
    '83'=>'Jharkhand','84'=>'Bihar','85'=>'Bihar',
];

function pincode_state(string $pin): ?string {
    $prefix = substr(trim($pin), 0, 2);
    return PINCODE_STATE[$prefix] ?? null;
}

function is_valid_indian_state(string $state): bool {
    $norm = strtolower(trim(preg_replace('/\s+/', ' ', $state)));
    return in_array($norm, INDIA_STATES);
}

function is_india(string $country): bool {
    return strtolower(trim($country)) === 'india';
}

/**
 * Validate a field map.
 * @param array $data   POST data
 * @param array $rules  ['fieldName' => 'ruleName', ...]
 * @return string[]     Array of error messages (empty = all valid)
 */
function validate(array $data, array $rules): array {
    $errors = [];
    foreach ($rules as $field => $rule) {
        $val = trim($data[$field] ?? '');
        switch ($rule) {
            case 'required':
                if ($val === '') $errors[$field] = ucfirst(str_replace('_',' ',$field)).' is required';
                break;
            case 'name':
            case 'full_name':
                if (!preg_match('/^[A-Za-z\s]{2,100}$/', $val))
                    $errors[$field] = 'Full name must contain letters only (min 2 chars)';
                break;
            case 'phone':
            case 'mobile':
                if (!preg_match('/^[6-9][0-9]{9}$/', $val))
                    $errors[$field] = 'Enter a valid 10-digit Indian mobile number';
                break;
            case 'pincode':
                if (!preg_match('/^[1-9][0-9]{5}$/', $val))
                    $errors[$field] = 'Enter a valid 6-digit pincode';
                break;
            case 'pincode_india':
                if (!preg_match('/^[1-9][0-9]{5}$/', $val)) {
                    $errors[$field] = 'Enter a valid 6-digit pincode';
                } elseif (!pincode_state($val)) {
                    $errors[$field] = 'Pincode not recognised — please verify';
                }
                break;
            case 'country':
                if (!is_india($val))
                    $errors[$field] = 'This website currently operates only in India';
                break;
            case 'state':
                if ($val !== '' && !is_valid_indian_state($val))
                    $errors[$field] = 'Enter a valid Indian state name';
                break;
            case 'ifsc':
                if (!preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', strtoupper($val)))
                    $errors[$field] = 'IFSC must be in format XXXX0YYYYYY (e.g. SBIN0001234)';
                break;
            case 'acc_num':
            case 'account_number':
                if (!preg_match('/^[0-9]{6,18}$/', $val))
                    $errors[$field] = 'Account number must be 6–18 digits';
                break;
            case 'acc_name':
            case 'account_name':
                if (!preg_match('/^[A-Za-z\s]{3,80}$/', $val))
                    $errors[$field] = 'Account holder name must contain letters only (min 3 chars)';
                break;
            case 'email':
                if ($val !== '' && !filter_var($val, FILTER_VALIDATE_EMAIL))
                    $errors[$field] = 'Enter a valid email address';
                break;
            case 'min2':
                if (strlen($val) < 2)
                    $errors[$field] = ucfirst(str_replace('_',' ',$field)).' is required (min 2 chars)';
                break;
        }
    }
    return $errors;
}

/**
 * Validate address fields and also cross-check pincode → state
 */
function validate_address(array $data): array {
    $errors = validate($data, [
        'country'   => 'country',
        'full_name' => 'name',
        'mobile'    => 'mobile',
        'flat'      => 'min2',
        'area'      => 'min2',
        'landmark'  => 'min2',
        'pincode'   => 'pincode_india',
        'city'      => 'min2',
        'state'     => 'state',
    ]);

    // Cross-check pincode vs state
    if (empty($errors['pincode']) && !empty($data['state']) && !empty($data['pincode'])) {
        $expectedState = pincode_state(trim($data['pincode']));
        $enteredState  = strtolower(trim($data['state'] ?? ''));
        if ($expectedState) {
            $expNorm = strtolower($expectedState);
            if ($enteredState && $enteredState !== $expNorm &&
                strpos($expNorm, $enteredState) === false &&
                strpos($enteredState, $expNorm) === false) {
                $errors['state'] = "Pincode {$data['pincode']} belongs to $expectedState, not '{$data['state']}'";
            }
        }
    }
    return $errors;
}