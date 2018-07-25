import static com.kms.katalon.core.checkpoint.CheckpointFactory.findCheckpoint
import static com.kms.katalon.core.testcase.TestCaseFactory.findTestCase
import static com.kms.katalon.core.testdata.TestDataFactory.findTestData
import static com.kms.katalon.core.testobject.ObjectRepository.findTestObject
import com.kms.katalon.core.checkpoint.Checkpoint as Checkpoint
import com.kms.katalon.core.checkpoint.CheckpointFactory as CheckpointFactory
import com.kms.katalon.core.mobile.keyword.MobileBuiltInKeywords as MobileBuiltInKeywords
import com.kms.katalon.core.mobile.keyword.MobileBuiltInKeywords as Mobile
import com.kms.katalon.core.model.FailureHandling as FailureHandling
import com.kms.katalon.core.testcase.TestCase as TestCase
import com.kms.katalon.core.testcase.TestCaseFactory as TestCaseFactory
import com.kms.katalon.core.testdata.TestData as TestData
import com.kms.katalon.core.testdata.TestDataFactory as TestDataFactory
import com.kms.katalon.core.testobject.ObjectRepository as ObjectRepository
import com.kms.katalon.core.testobject.TestObject as TestObject
import com.kms.katalon.core.webservice.keyword.WSBuiltInKeywords as WSBuiltInKeywords
import com.kms.katalon.core.webservice.keyword.WSBuiltInKeywords as WS
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUiBuiltInKeywords
import com.kms.katalon.core.webui.keyword.WebUiBuiltInKeywords as WebUI
import internal.GlobalVariable as GlobalVariable
import org.openqa.selenium.Keys as Keys

WebUI.openBrowser('')

WebUI.navigateToUrl('http://localhost:8000')

WebUI.click(findTestObject('Page_Products  Casa Bonita/a_Add to cart'))

WebUI.delay(5)

WebUI.click(findTestObject('Page_Products  Casa Bonita/a_View cart'))

WebUI.click(findTestObject('Page_Cart  Casa Bonita/a_Proceed to checkout'))

WebUI.delay(5)

WebUI.setText(findTestObject('Page_Checkout  Casa Bonita/input_billing_first_name'), 'ALDO')

WebUI.setText(findTestObject('Page_Checkout  Casa Bonita/input_billing_last_name'), 'VALDEZ')

WebUI.setText(findTestObject('Page_Checkout  Casa Bonita/input_billing_company'), 'PAYSTAND')

WebUI.setText(findTestObject('Page_Checkout  Casa Bonita/input_billing_address_1'), 'STREET')

WebUI.setText(findTestObject('Page_Checkout  Casa Bonita/input_billing_city'), 'GUADALAJARA')

WebUI.navigateToUrl(GlobalVariable.domain)

WebUI.click(findTestObject('Page_Checkout  Casa Bonita/span_select2-selection select2'))

WebUI.sendKeys(findTestObject(null), 'JALISCO')

WebUI.click(findTestObject('Page_Checkout  Casa Bonita/li_Jalisco'))

WebUI.setText(findTestObject('Page_Checkout  Casa Bonita/input_billing_postcode'), '42000')

WebUI.setText(findTestObject('Page_Checkout  Casa Bonita/input_billing_phone'), '332445155')

WebUI.setText(findTestObject('Page_Checkout  Casa Bonita/input_billing_email'), 'avaldez@paystand.com')

WebUI.click(findTestObject('Page_Checkout  Casa Bonita/label_PayStand (Credit Card eC'))

WebUI.click(findTestObject('Page_Checkout  Casa Bonita/button_Pay With Paystand'))

WebUI.delay(15)

WebUI.click(findTestObject('Page_Checkout  Casa Bonita/button_Card'))

WebUI.setText(findTestObject('Page_Checkout  Casa Bonita/input_card_number'), '4242 4242 4242 4242')

WebUI.setText(findTestObject('Page_Checkout  Casa Bonita/input_card_expiry'), '12 / 21')

WebUI.setText(findTestObject('Page_Checkout  Casa Bonita/input_card_security_code'), '123')

WebUI.click(findTestObject('Page_Checkout  Casa Bonita/button_Enter Billing Informati'))

WebUI.setText(findTestObject('Page_Checkout  Casa Bonita/input_address_state'), 'jalisco')

WebUI.click(findTestObject('Page_Checkout  Casa Bonita/span_Jalisco'))

WebUI.click(findTestObject('Page_Checkout  Casa Bonita/button_Pay 30.99 USD'))

WebUI.delay(15)

WebUI.verifyElementPresent(findTestObject('Page_Checkout  Casa Bonita/p_Thank you. Your order has be'), 0)

WebUI.delay(10)

WebUI.closeBrowser()

