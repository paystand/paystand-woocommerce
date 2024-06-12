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

WebUI.navigateToUrl(GlobalVariable.domain)

WebUI.setText(findTestObject('Page_My Account  MyWordpress (4)/input_username'), GlobalVariable.user)

WebUI.setText(findTestObject('Page_My Account  MyWordpress (3)/input_password'), GlobalVariable.password)

WebUI.sendKeys(findTestObject('Page_My Account  MyWordpress (3)/input_login'), Keys.chord(Keys.ENTER))

WebUI.click(findTestObject('Page_My Account  MyWordpress (3)/a_Payment methods'))

WebUI.click(findTestObject('Page_My Account  MyWordpress (3)/a_Add payment method'))

WebUI.click(findTestObject('Page_My Account  MyWordpress (3)/input_place_order'))

WebUI.setText(findTestObject('Page_My Account  MyWordpress (3)/input_payer_email'), 'aldo@pays.com')

WebUI.click(findTestObject('Page_My Account  MyWordpress (3)/div_logo-link  bofa-link'))

WebUI.setText(findTestObject('Page_My Account  MyWordpress (3)/input_login_username'), 'paystand_test')

WebUI.setText(findTestObject('Page_My Account  MyWordpress (3)/input_login_password'), 'paystand_good')

WebUI.sendKeys(findTestObject('Page_My Account  MyWordpress (3)/button_Secure Login'), Keys.chord(Keys.ENTER))

WebUI.setText(findTestObject('Page_My Account  MyWordpress (3)/input_mfa_answer_'), 'tomato')

WebUI.sendKeys(findTestObject('Page_My Account  MyWordpress (3)/button_Submit Answers'), Keys.chord(Keys.ENTER))

WebUI.setText(findTestObject('Page_My Account  MyWordpress (3)/input_mfa_answer_nameOnAccount'), 'aldo')

WebUI.click(findTestObject('Page_My Account  MyWordpress (3)/Page_My Account  MyWordpress/md-select_mfa_answer_accountHo'))

WebUI.click(findTestObject('Page_My Account  MyWordpress (3)/md-option_individual'), FailureHandling.STOP_ON_FAILURE)

WebUI.sendKeys(findTestObject('Page_My Account  MyWordpress (3)/button_Submit Answers'), Keys.chord(Keys.ENTER))

WebUI.click(findTestObject('Page_My Account  MyWordpress (3)/div_md-off'))

WebUI.click(findTestObject('Page_My Account  MyWordpress (3)/button_Select Fund'))

WebUI.doubleClick(findTestObject('Page_My Account  MyWordpress (3)/input_address_street'))

WebUI.setText(findTestObject('Page_My Account  MyWordpress (3)/input_address_street'), 'street')

WebUI.setText(findTestObject('Page_My Account  MyWordpress (3)/input_address_city'), 'guadalajara')

WebUI.setText(findTestObject('Page_My Account  MyWordpress (3)/input_address_postal'), '62000')

WebUI.setText(findTestObject('Page_My Account  MyWordpress (3)/input_address_state'), 'Alabama')

WebUI.click(findTestObject('Page_My Account  MyWordpress (3)/button_Save  Bank For Future P'), FailureHandling.STOP_ON_FAILURE)

WebUI.click(findTestObject('Page_My Account  MyWordpress (3)/a_Logout'))

WebUI.closeBrowser()

